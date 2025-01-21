<?php

namespace App\Http\Controllers;

use App\Models\Opacity;
use App\Models\Air;
use App\Models\Cems;
use App\Models\Flare;
use App\Models\Weather;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Validation\Factory as ValidatorFactory;

class HistoryController extends Controller
{
    protected $validatorFactory;

    public function __construct(ValidatorFactory $validatorFactory)
    {
        $this->validatorFactory = $validatorFactory;
    }

    public function weatherhistory(Request $request){
        $rules = [
            'air' => 'required', //日期
        ];

        $validator = $this->validatorFactory->make($request->json()->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['statuscode' => '412', 'status' => '必要資料未帶入']);
        }

        $sql = Weather::orderby('CreateTime', 'desc')->limit(24)->get();

        if(!$sql){
            return response()->json(['statuscode' => '204', 'status' => '資料查詢失敗或無資料', 'data' => []]);
        }

        switch ($request->air) {
            case '溫度':
                $key = 'airTemperature';
                break;
            
            case '風速':
                $key = 'windSpeed';
                break;

            case '風向':
                $key = 'windDirection';
                break;

            case '濕度':
                $key = 'RelativeHumidity';
                break;
        }

        $sql = $sql->sortBy('CreateTime');
        $timelist = $sql->pluck('CreateTime');
        $datalist = $sql->pluck('Weather_Data')->map(function ($item){
            return json_decode($item,true);
        })->pluck($key);

        $list = [
            'time' => $timelist,
            'data' => $datalist
        ];
        
        return response()->json(['statuscode' => '200', 'status' => '資料查詢成功', 'data' => $list]);
    }

    public function aqihistory(Request $request){
        $rules = [
            'aqi' => 'required', //日期
        ];

        $validator = $this->validatorFactory->make($request->json()->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['statuscode' => '412', 'status' => '必要資料未帶入']);
        }

        $sql = Air::orderby('CreateTime', 'desc')->limit(24)->get();

        if(!$sql){
            return response()->json(['statuscode' => '204', 'status' => '資料查詢失敗或無資料', 'data' => []]);
        }

        $key = strtolower($request->aqi);
        $sql = $sql->sortBy('CreateTime');
        $timelist = $sql->pluck('CreateTime');
        $datalist = $sql->pluck('Air_Data')->flatMap(function ($item) use ($key){
            $item = json_decode($item, true);
            return collect($item)->where('type', $key)->pluck('value');
        });

        $list = [
            'time' => $timelist,
            'data' => $datalist
        ];
        
        return response()->json(['statuscode' => '200', 'status' => '資料查詢成功', 'data' => $list]);
    }

    public function opacityhistory(Request $request){
        $rules = [
            'factory' => 'required', //廠區
            'opacity' => 'required' //編號
        ];

        $validator = $this->validatorFactory->make($request->json()->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['statuscode' => '412', 'status' => '必要資料未帶入']);
        }

        $sql = Opacity::where('Factory_Code', $request->factory)->where('Opacity_Code', $request->opacity)
                ->orderby('CreateTime', 'desc')->limit(24)->get();

        if(!$sql){
            return response()->json(['statuscode' => '204', 'status' => '資料查詢失敗或無資料', 'data' => []]);
        }

        $sql = $sql->sortBy('CreateTime');
        $timelist = $sql->pluck('CreateTime');
        $datalist = $sql->pluck('Opacity_data')->map(function ($item) {
            return is_numeric($item) ? (float) $item : $item; // 確保是數值型
        });

        $list = [
            'time' => $timelist,
            'data' => $datalist
        ];
        
        return response()->json(['statuscode' => '200', 'status' => '資料查詢成功', 'data' => $list]);
    }

    public function selectopacity(){
        $sql = Opacity::get(['Factory_Code','Opacity_Code']);

        if(!$sql){
            return response()->json(['statuscode' => '204', 'status' => '資料查詢失敗或無資料', 'data' => []]);
        }

        $factory = $sql->groupBy(function ($item) {
            return $item['Factory_Code'];
        })->keys();

        $senser = $sql->groupBy(function ($item) {
            return $item['Factory_Code'].'-'.$item['Opacity_Code'];
        })->keys();

        $opacity = $senser->reduce(function ($data, $item) use ($factory){
            $factory->each(function ($oitem) use ($item ,&$data){
                if(Str::startsWith($item, $oitem)){
                    $odata = explode("-", $item);
                    $data[$oitem][] = $odata[1];
                }
            });
            
            return $data;
        }, []);

        $opacity = collect($opacity)->map(function ($values, $key) {
            return ['factory' => $key, 'opacity' => array_values($values)];
        })->values();

        return response()->json(['statuscode' => '200', 'status' => '資料查詢成功', 'data' => $opacity]);
    }

    public function cemshistory(Request $request){
        $rules = [
            'factory' => 'required', //廠區
            'cems' => 'required', //cems 編號
            'type' => 'required', //十五分鐘或一小時
            'gas' => 'required' //氣體
        ];

        $validator = $this->validatorFactory->make($request->json()->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['statuscode' => '412', 'status' => '必要資料未帶入']);
        }

        $sql = Cems::where('CEMS_Type', $request->type)->where('Factory_Code', $request->factory)->where('CEMS_Code', $request->cems)
                ->orderby('CreateTime', 'desc')->limit(24)->get();

        if(!$sql){
            return response()->json(['statuscode' => '204', 'status' => '資料查詢失敗或無資料', 'data' => []]);
        }

        $key = $request->gas;
        $sql = $sql->sortBy('CreateTime');
        $timelist = $sql->pluck('CreateTime');
        $datalist = $sql->pluck('CEMS_Data')->map(function ($item) use ($key){
            $data = json_decode($item,true);
            return collect($data)->where('type', $key)->pluck('value');
        })->flatten();

        $list = [
            'time' => $timelist,
            'data' => $datalist
        ];
        
        return response()->json(['statuscode' => '200', 'status' => '資料查詢成功', 'data' => $list]);
    }

    public function selectcems(){
        $sql = Cems::where('CEMS_Type', '15_min')->get(['Factory_Code','CEMS_Code']);

        if(!$sql){
            return response()->json(['statuscode' => '204', 'status' => '資料查詢失敗或無資料', 'data' => []]);
        }

        $factory = $sql->groupBy(function ($item) {
            return $item['Factory_Code'];
        })->keys();

        $senser = $sql->groupBy(function ($item) {
            return $item['Factory_Code'].'-'.$item['CEMS_Code'];
        })->keys();

        $cems = $senser->reduce(function ($data, $item) use ($factory){
            $factory->each(function ($citem) use ($item ,&$data){
                if(Str::startsWith($item, $citem)){
                    $odata = explode("-", $item);
                    $data[$citem][] = $odata[1];
                }
            });
            
            return $data;
        }, []);

        $cems = collect($cems)->map(function ($values, $key) {
            return ['factory' => $key, 'cems' => array_values($values)];
        })->values();

        return response()->json(['statuscode' => '200', 'status' => '資料查詢成功', 'data' => $cems]);
    }

    public function flarehistory(Request $request){
        $rules = [
            'factory' => 'required', //廠區
            'flare' => 'required', //cems 編號
            'type' => 'required' //十五分鐘或一小時
        ];

        $validator = $this->validatorFactory->make($request->json()->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['statuscode' => '412', 'status' => '必要資料未帶入']);
        }

        $sql = Flare::where('Flare_Type', $request->type)->where('Factory_Code', $request->factory)->where('Flare_Code', $request->flare)
                ->orderby('CreateTime', 'desc')->limit(24)->get();

        if(!$sql){
            return response()->json(['statuscode' => '204', 'status' => '資料查詢失敗或無資料', 'data' => []]);
        }

        $sql = $sql->sortBy('CreateTime');
        $timelist = $sql->pluck('CreateTime');
        $datalist = $sql->pluck('Flare_Data')->map(function ($item){
            return (json_decode($item,true));
        });

        $datalist = collect($datalist)
            ->flatten(1) // 將多層結構展平
            ->groupBy('type') // 按照 'type' 分組
            ->map(function ($items, $type) {
                return [
                    'type' => $type,
                    'values' => $items->pluck('value')->all(), // 提取 'value' 值
                ];
            })
            ->values(); // 重新索引
        
        $list = [
            'time' => $timelist,
            'data' => $datalist
        ];
        
        return response()->json(['statuscode' => '200', 'status' => '資料查詢成功', 'data' => $list]);
    }

    public function selectflare(){
        $sql = Flare::where('Flare_Type', '15_min')->get(['Factory_Code','Flare_Code']);

        if(!$sql){
            return response()->json(['statuscode' => '204', 'status' => '資料查詢失敗或無資料', 'data' => []]);
        }

        $factory = $sql->groupBy(function ($item) {
            return $item['Factory_Code'];
        })->keys();

        $senser = $sql->groupBy(function ($item) {
            return $item['Factory_Code'].'-'.$item['Flare_Code'];
        })->keys();

        $flare = $senser->reduce(function ($data, $item) use ($factory){
            $factory->each(function ($citem) use ($item ,&$data){
                if(Str::startsWith($item, $citem)){
                    $odata = explode("-", $item);
                    $data[$citem][] = $odata[1];
                }
            });
            
            return $data;
        }, []);

        $cems = collect($flare)->map(function ($values, $key) {
            return ['factory' => $key, 'flare' => array_values($values)];
        })->values();

        return response()->json(['statuscode' => '200', 'status' => '資料查詢成功', 'data' => $cems]);
    }
}
