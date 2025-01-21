<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use DOMDocument;
use App\Models\Sensor;
use App\Models\Flare;
use App\Models\Opacity;
use App\Models\Air;
use App\Models\Cems;
use App\Models\Weather;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

ini_set('max_execution_time', 180); // 增加腳本執行時間限制
ini_set('default_socket_timeout', 60); // 可選：設置套接字超時
class WeatherController extends Controller
{
    // 每小時執行一次
    public function getWeather(){
        // 定義 API 基本資訊與參數
        $baseUrl = 'https://opendata.cwa.gov.tw/api/v1/rest/datastore/O-A0003-001';
        $apiKey = 'CWA-3BD84D8A-6354-4147-A7A4-F3C4F1535589';
        $params = [
            'Authorization' => $apiKey,
            'format' => 'JSON',
            'StationId' => 'V2K620',
        ];

        // 發送 HTTP 請求
        $weatherapi = Http::withOptions(['verify' => false])
                        ->get($baseUrl, $params)
                        ->json();
        $result = $weatherapi['records']['Station'][0];

        $searchtime = Carbon::parse($result['ObsTime']['DateTime'])->format('Y-m-d H:i:s');

        $data = [
            'windDirection' => $result['WeatherElement']['WindDirection'], //風向
            'windSpeed' => $result['WeatherElement']['WindSpeed'], //風速
            'airTemperature'=> $result['WeatherElement']['AirTemperature'], //氣溫
            'RelativeHumidity'=> $result['WeatherElement']['RelativeHumidity'], //氣溫
        ];

        $weather = [
            'Weather_Data' => json_encode($data,JSON_UNESCAPED_UNICODE),
            'CreateTime' => $searchtime
        ];

        $sql = Weather::insert($weather);
        if($sql){
            return response()->json(['status' => '200']);
        }else{
            return response()->json(['status' => '502']);
        }

        return $result;
    }

    //舊的
    public function getCEMS(){
        // ini_set('default_socket_timeout', 60);
        $gettime = Carbon::now()->format('Y-m-d H:i');

        // https://www.yesylepb.com.tw/yunlinapi/api/CEMS15min?apikey=d!z@WWg2XCviREcC÷
        $baseUrl = 'https://www.yesylepb.com.tw/yunlinapi/api/CEMS15min';
        $apiKey = 'd!z@WWg2XCviREcC';
        $params = [
            'apikey' => $apiKey
        ];

        // 發送 HTTP 請求
        $cemsapi = Http::retry(3, 5000,function ($exception, $request) {
                        // 重試條件: 發生超時或 5xx 錯誤
                        return $exception instanceof \Illuminate\Http\Client\RequestException 
                            || ($exception->response?->status() >= 500);
                    })
                    ->withOptions(['verify' => false,'timeout' => 120,'connect_timeout' => 10])
                    ->get($baseUrl, $params);

        if ($cemsapi->successful()) {
            $result = $cemsapi->json();
            $collection = collect($result)->groupBy('RegID');
            
            return $collection;
        } else {
            Log::error('API 請求失敗', ['status' => $cemsapi->status()]);
        }
    }

    // 一定要15分整執行跟00整
    public function newCEMS(){
        $baseUrl = 'https://www.ylepb.net/epbt/CEMSDetailNNew.asp';

        $sensor = Sensor::whereNotNull('CEMS_Code')->get(['Factory_Code','CEMS_Code']);

        $cemslist = [];

        foreach ($sensor as $sitem) {
            $params = [
                'FNO' => $sitem['Factory_Code'],
                'PNO' => $sitem['CEMS_Code'],
                'DType' => 1
            ];

            // 發送 HTTP 請求
            $cemsapi = Http::withOptions(['verify' => false,'timeout' => 60,'connect_timeout' => 10])
                    ->get($baseUrl, $params);

            if ($cemsapi->successful()) {
                $dom = new DOMDocument();
                libxml_use_internal_errors(true);
                $dom->loadHTML($cemsapi);
                libxml_clear_errors();

                $tables_td = $dom->getElementsByTagName('td');
                // 獲取當前時間的分鐘數
                $currentMinute = (int)date('i');
                // $currentMinute = 0;

                $cems = [];
                foreach ($tables_td as $tables_td) {
                    //取td裡面的value
                    $result = (string)$tables_td->nodeValue;
                    if($result != '  '){
                        array_push($cems, $result);
                    }
                }

                $targets = ['氧氣', '排放流率值', '一氧化碳', '氮氧化物', '二氧化硫'];

                
                // 根據分鐘數判斷需要的數據類型
                $requiredCategory = ($currentMinute === 0) ? '監測設施一小時數據平均值' : '監測設施十五分鐘數據紀錄值';
                $currentCategory = null;

                // 找到目標標題的位置
                $splitKey = array_search("監測設施十五分鐘數據紀錄值", $cems);
                // 分割陣列
                $part1 = array_slice($cems, 0, $splitKey); // 第一部分
                $part2 = array_slice($cems, $splitKey);    // 第二部分

                $cems = ($currentMinute === 0) ? $part1 : $part2;

                // 解析數據
                foreach ($cems as $item) {
                    $item = trim($item);

                    // 判斷分類標題
                    if (strpos($item, $requiredCategory) !== false) {
                        $currentCategory = ($currentMinute === 0) ? '1_hour' : '15_min';
                    }

                    // 判斷是否為目標字段
                    if (in_array($item, $targets)) {
                        $index = array_search($item, $cems);
                        if ($index !== false) {
                            // 提取值及單位
                            $value = trim($cems[$index + 1] ?? '');
                            $unit = trim($cems[$index + 2] ?? '');

                            // 儲存至對應分類
                            if ($currentCategory) {
                                $results[] = [
                                    'type' => $item,
                                    'value' => $value,
                                    'unit' => $unit,
                                ];
                            }
                        }
                    }
                }

                if(!empty($results)){
                    // 輸出結果
                    $cemslist[] = [
                        'Factory_Code' => $sitem['Factory_Code'],
                        'CEMS_Code' => $sitem['CEMS_Code'],
                        'CEMS_Type' => $currentCategory,
                        'CEMS_Data' => json_encode($results,JSON_UNESCAPED_UNICODE),
                        'CreateTime' => Carbon::now()->format('Y-m-d H:i:s')
                    ];
                    $results = [];
                }

            } else {
                Log::error('API 請求失敗', ['status' => $cemsapi->status()]);
            }
        }

        // return ($cemslist);
        $sql = Cems::insert($cemslist);
        if($sql){
                return response()->json(['status' => '200']);
            }else{
                return response()->json(['status' => '502']);
            }
    }

    // 每小時執行一次
    public function getAQI(){
        // https://data.moenv.gov.tw/api/v2/aqx_p_432?language=zh&api_key=23ce9e76-c1a6-45c1-a9b8-bf16485c0e1e
        $baseUrl = 'https://data.moenv.gov.tw/api/v2/aqx_p_432';
        $apiKey = '23ce9e76-c1a6-45c1-a9b8-bf16485c0e1e';
        $params = [
            'language' => 'zh',
            'api_key' => $apiKey
        ];

        // 發送 HTTP 請求
        $aqiapi = Http::withOptions(['verify' => false,'timeout' => 60])
                ->get($baseUrl, $params);

        if ($aqiapi->successful()) {
            $result = $aqiapi->json();
            $result = $result['records'];
            $collection = collect($result)->map(function($item, $key){
                if($item['siteid'] == '83'){
                    $data = [
                        [
                            'type' => 'aqi',
                            'value' => $item['aqi'],
                            'unit' => '',
                        ],
                        [
                            'type' => 'so2',
                            'value' => $item['so2'],
                            'unit' => 'ppb',
                        ],
                        [
                            'type' => 'co',
                            'value' => $item['co'],
                            'unit' => 'ppm',
                        ],
                        [
                            'type' => 'o3',
                            'value' => $item['o3'],
                            'unit' => 'ppb',
                        ],
                        [
                            'type' => 'pm10',
                            'value' => $item['pm10'],
                            'unit' => 'μg/m3',
                        ],
                        [
                            'type' => 'pm2.5',
                            'value' => $item['pm2.5'],
                            'unit' => 'μg/m3',
                        ],
                        [
                            'type' => 'no2',
                            'value' => $item['no2'],
                            'unit' => 'ppb',
                        ]
                    ];
                    
                    return [
                        'Air_Data' => (json_encode($data,JSON_UNESCAPED_UNICODE)),
                        'CreateTime' => Carbon::parse($item['publishtime'])->format('Y-m-d H:i:s')
                    ];

                }
            })->filter()->values()->first();

            // return $collection;
            $sql = Air::insert($collection);
            if($sql){
                return response()->json(['status' => '200']);
            }else{
                return response()->json(['status' => '502']);
            }

        } else {
            Log::error('API 請求失敗', ['status' => $aqiapi->status()]);
        }
    }

    // 六分鐘執行一次
    public function getOpacity(){
        // https://www.yesylepb.com.tw/yunlinapi/api/CEMS6min?apikey=d!z@WWg2XCviREcC
        $baseUrl = 'https://www.yesylepb.com.tw/yunlinapi/api/CEMS6min';
        $apiKey = 'd!z@WWg2XCviREcC';
        $params = [
            'apikey' => $apiKey
        ];

        // 發送 HTTP 請求
        $opacityapi = Http::withOptions(['verify' => false,'timeout' => 60])
                ->get($baseUrl, $params);
        
        if ($opacityapi->successful()) {
            $result = $opacityapi->json();
            // 先用map將資料都整理成所需資料接著透過groupby的方式進行集合
            $collection = collect($result)->map(function ($item, $key) {
                return [
                    'RegID' => $item['RegID'],
                    'SensorID' => $item['SensorID'],
                    'Value' => $item['Value'],
                    'DateTime' => Carbon::parse($item['DateTime'])->format('Y-m-d H:i:s')
                ];
            })->groupBy('RegID');

            $list = $collection->map(function($item, $key){
                $data = [];
                foreach ($item as $odata) {
                    $data[] = [
                        'Factory_Code' => $key,
                        'Opacity_Code' => $odata['SensorID'],
                        'Opacity_data' => (float)$odata['Value'],
                        'CreateTime' => Carbon::parse($odata['DateTime'])->format('Y-m-d H:i:s')
                    ];
                };
                

                // 按 CreateTime 排序，降序排列
                usort($data, function ($a, $b) {
                    return strtotime($b['CreateTime']) - strtotime($a['CreateTime']);
                });

                // 移除重複，只保留最新的 Opacity_Code 資料
                $uniqueRecords = [];
                $seenOpacityCodes = [];

                foreach ($data as $data) {
                    if (!in_array($data['Opacity_Code'], $seenOpacityCodes)) {
                        $uniqueRecords[] = $data;
                        $seenOpacityCodes[] = $data['Opacity_Code'];
                    }
                }

                return $uniqueRecords;
            });

            // 把第一層攤開
            $list = ($list->values()->flatten(1)->all());

            $sql = Opacity::insert($list);

            if($sql){
                return response()->json(['status' => '200']);
            }else{
                return response()->json(['status' => '502']);
            }
        }else {
            Log::error('API 請求失敗', ['status' => $opacityapi->status()]);
        }
    }

    // 一定要5分整執行每15分鐘一次
    public function getFlare(){ 
        $baseUrl = 'https://www.ylepb.net/epbt/CEMSDetailN.asp';

        $sensor = Sensor::whereNotNull('Flare_Code')->get(['Factory_Code','Flare_Code']);

        $flarelist = [];

        foreach ($sensor as $sitem) {
            $params = [
                'FNO' => $sitem['Factory_Code'],
                'PNO' => $sitem['Flare_Code'],
                'DType' => 1,
                'DataT' => 'FL_'
            ];

            // 發送 HTTP 請求
            $flareapi = Http::withOptions(['verify' => false,'timeout' => 60,'connect_timeout' => 10])
                    ->get($baseUrl, $params);

            if ($flareapi->successful()) {
                $dom = new DOMDocument();
                libxml_use_internal_errors(true);
                $dom->loadHTML($flareapi);
                libxml_clear_errors();

                $tables_td = $dom->getElementsByTagName('td');

                // 獲取當前時間的分鐘數
                $currentMinute = (int)date('i');
                // $currentMinute = 5;

                $flare = [];
                foreach ($tables_td as $tables_td) {
                    //取td裡面的value
                    $result = (string)$tables_td->nodeValue;
                    array_push($flare, $result);
                }

                // 根據分鐘數判斷需要的數據類型
                $requiredCategory = ($currentMinute === 5) ? '排放流率一小時監測紀錄值' : '排放流率十五分鐘監測紀錄值';
                $currentCategory = ($currentMinute === 5) ? '1_hour' : '15_min';;

                // 找到目標標題的位置
                $splitKey = array_search("排放流率一小時監測紀錄值", $flare);
                // 分割陣列
                $part1 = array_slice($flare, 0, $splitKey); // 一小時
                $part2 = array_slice($flare, $splitKey);    // 15分鐘

                $flare = ($currentMinute === 5) ? collect($part2) : collect($part1);

                $values = [];

                foreach ($flare as $key => $value) {
                    if ($value === $requiredCategory) {
                        // 檢查對應的數值位置
                        $nextIndex = $key + 3; // "排放流率一小時監測紀錄值" 後第3個位置
                        $nexttype = $key + 1;
                        if (isset($flare[$nextIndex])) {
                            if(is_numeric($flare[$nextIndex])){
                                $values[] = [
                                    'type' => is_null($flare[$nexttype]) ? "A" : $flare[$nexttype],
                                    'value' => $flare[$nextIndex],
                                    'unit' => 'CMH'
                                ];
                            }else{
                                $values[] = [
                                    'type' => is_null($flare[$nexttype]) ? "A" : $flare[$nexttype],
                                    'value' => 0,
                                    'unit' => 'CMH'
                                ];
                            }
                        }
                    }
                }

                $flarelist[] = [
                    'Factory_Code' => $sitem['Factory_Code'],
                    'Flare_Code' => $sitem['Flare_Code'],
                    'Flare_Type' => $currentCategory,
                    'Flare_Data' => json_encode($values,JSON_UNESCAPED_UNICODE),
                    'CreateTime' => Carbon::now()->format('Y-m-d H:i:s')
                ];
                
            }else{
                Log::error('API 請求失敗', ['status' => $flareapi->status()]);
            }
        }

        $sql = Flare::insert($flarelist);
        if($sql){
            return response()->json(['status' => '200']);
        }else{
            return response()->json(['status' => '502']);
        }

    }
}
