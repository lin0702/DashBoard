<?php

namespace App\Http\Controllers;

use App\Models\Air;
use App\Models\Weather;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Factory as ValidatorFactory;
use Illuminate\Http\Request;

class ImmediateController extends Controller
{
    protected $validatorFactory;

    public function __construct(ValidatorFactory $validatorFactory)
    {
        $this->validatorFactory = $validatorFactory;
    }
    
    public function nowweather(Request $request){

        $rules = [
            'createTime' => 'required'
        ];

        $validator = $this->validatorFactory->make($request->json()->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['statuscode' => '412', 'status' => '必要資料未帶入']);
        }

        $sql = Weather::whereLike('CreateTime', $request->createTime.'%')->Orderby('CreateTime','desc')->first();

        if(!$sql){
             return response()->json(['statuscode' => '204', 'status' => '查無資料', 'data' => []]);
        }

        $sql->Weather_Data = json_decode($sql->Weather_Data);

        return response()->json(['statuscode' => '200', 'status' => '資料查詢成功', 'data' => $sql]);

    }

    public function nowair(Request $request){
        
        $rules = [
            'createTime' => 'required'
        ];

        $validator = $this->validatorFactory->make($request->json()->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['statuscode' => '412', 'status' => '必要資料未帶入']);
        }

        $sql = Air::whereLike('CreateTime', $request->createTime.'%')->Orderby('CreateTime','desc')->first();
        
        if(!$sql){
             return response()->json(['statuscode' => '204', 'status' => '查無資料', 'data' => []]);
        }

        $sql->Air_Data = json_decode($sql->Air_Data);

        return response()->json(['statuscode' => '200', 'status' => '資料查詢成功', 'data' => $sql]);

    }

    public function nowcems(Request $request){
        // 廠區的管制編號
        $rules = [
            'factory' => 'required',
            'createTime' => 'required'
        ];

        $validator = $this->validatorFactory->make($request->json()->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['statuscode' => '412', 'status' => '必要資料未帶入']);
        }
        $factoryCode = $request->json('factory');

        $result1 = DB::table('CEMS_event as t')
            ->join(DB::raw('(SELECT CEMS_Code, MAX(CreateTime) AS latest_time
                            FROM CEMS_event
                            WHERE Factory_Code = ? AND CEMS_Type = "15_min"
                            GROUP BY CEMS_Code) as latest_records'), function ($join) {
                $join->on('t.CEMS_Code', '=', 'latest_records.CEMS_Code')
                    ->on('t.CreateTime', '=', 'latest_records.latest_time');
            })
            ->addBinding($factoryCode, 'select')
            ->where('t.Factory_Code', $factoryCode)
            ->whereLike('t.CreateTime', $request->createTime.'%')
            ->orderBy('t.CreateTime', 'DESC')
            ->get();

        $result2 = DB::table('CEMS_event as t')
            ->join(DB::raw('(SELECT CEMS_Code, MAX(CreateTime) AS latest_time
                            FROM CEMS_event
                            WHERE Factory_Code = ? AND CEMS_Type = "1_hour"
                            GROUP BY CEMS_Code) as latest_records'), function ($join) {
                $join->on('t.CEMS_Code', '=', 'latest_records.CEMS_Code')
                    ->on('t.CreateTime', '=', 'latest_records.latest_time');
            })
            ->addBinding($factoryCode, 'select')
            ->where('t.Factory_Code', $factoryCode)
            ->whereLike('t.CreateTime', $request->createTime.'%')
            ->orderBy('t.CreateTime', 'DESC')
            ->get();

        if ($result1->isEmpty() && $result2->isEmpty()) {
            return response()->json(['statuscode' => '204', 'status' => '查無資料', 'data' => []]);
        }

        $result = $result1->merge($result2);
        $result = collect($result);
        $sql = $result->map(function($item) {
            $itemArray = (array) $item;
            $itemArray['CEMS_Data'] = json_decode($item->CEMS_Data, true); // 轉换 CEMS_Data 
            return $itemArray;
        });

        $groupedData = $sql->groupBy('CEMS_Code')
                        ->map(function ($entries, $cemsCode) {
                            $firstEntry = $entries->first();

                            $grouped = [
                                "Factory_Code" => $firstEntry['Factory_Code'],
                                "CEMS_Code" => $cemsCode,
                                "CEMS_Data" => [],
                                "CreateTime" => $entries->max('CreateTime'),
                                "latest_time" => $entries->max('latest_time'),
                            ];

                            $cemsData = [];
                            foreach ($entries as $entry) {
                                foreach ($entry['CEMS_Data'] as $item) {
                                    $type = $item['type'];
                                    if (!isset($cemsData[$type])) {
                                        $cemsData[$type] = ['unit' => $item['unit']];
                                    }
                                    $cemsData[$type][$entry['CEMS_Type']] = (float) $item['value'];
                                }
                            }

                            // 调整键的顺序为 15_min、1_hour、unit
                            $grouped['CEMS_Data'] = collect($cemsData)->map(function ($data) {
                                return collect($data)
                                    ->sortBy(function ($value, $key) {
                                        // 设置键的排序优先级
                                        $order = ['15_min', '1_hour', 'unit'];
                                        return array_search($key, $order) ?? PHP_INT_MAX;
                                    })
                                    ->toArray();
                            })->toArray();

                            return $grouped;
                        })
                        ->values();

        return response()->json(['statuscode' => '200', 'status' => '成功', 'data' => $groupedData->values()->toArray()]);
    }

    public function nowopacity(Request $request){
        // 廠區的管制編號
        $rules = [
            'factory' => 'required',
            'createTime' => 'required'
        ];

        $validator = $this->validatorFactory->make($request->json()->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['statuscode' => '412', 'status' => '必要資料未帶入']);
        }
        $factoryCode = $request->json('factory');

        $result = DB::table('Opacity_event as t')
            ->join(DB::raw('(SELECT Opacity_Code, MAX(CreateTime) AS latest_time
                            FROM Opacity_event
                            WHERE Factory_Code = ?
                            GROUP BY Opacity_Code) as latest_records'), function ($join) {
                $join->on('t.Opacity_Code', '=', 'latest_records.Opacity_Code')
                    ->on('t.CreateTime', '=', 'latest_records.latest_time');
            })
            ->addBinding($factoryCode, 'select')
            ->where('t.Factory_Code', $factoryCode)
            ->whereLike('t.CreateTime', $request->createTime.'%')
            ->orderBy('t.CreateTime', 'DESC')
            ->get();
        
        if(!$result){
            return response()->json(['statuscode' => '204', 'status' => '查無資料', 'data' => []]);
        }

        $result = collect($result);
        $sql = $result->map(function($item) {
            $itemArray = (array) $item; // 将对象转换为数组
            $itemArray['Opacity_data'] = json_decode($item->Opacity_data, true); // 转换 CEMS_Data 并覆盖
            return $itemArray; // 返回修改后的数组
        });

        return response()->json(['statuscode' => '200', 'status' => '資料查詢成功', 'data' => $sql]);
    }

    public function nowflare(Request $request){
        // 廠區的管制編號
        $rules = [
            'factory' => 'required',
            'createTime' => 'required'
        ];

        $validator = $this->validatorFactory->make($request->json()->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['statuscode' => '412', 'status' => '必要資料未帶入']);
        }
        $factoryCode = $request->factory;

        $result1 = DB::table('Flare_event as t')
            ->join(DB::raw('(SELECT Flare_Code, MAX(CreateTime) AS latest_time
                            FROM Flare_event
                            WHERE Factory_Code = ? AND Flare_Type = "15_min"
                            GROUP BY Flare_Code) as latest_records'), function ($join) {
                $join->on('t.Flare_Code', '=', 'latest_records.Flare_Code')
                    ->on('t.CreateTime', '=', 'latest_records.latest_time');
            })
            ->addBinding($factoryCode, 'select')
            ->where('t.Factory_Code', $factoryCode)
            ->whereLike('t.CreateTime', $request->createTime.'%')
            ->orderBy('t.CreateTime', 'DESC')
            ->get();

        $result2 = DB::table('Flare_event as t')
            ->join(DB::raw('(SELECT Flare_Code, MAX(CreateTime) AS latest_time
                            FROM Flare_event
                            WHERE Factory_Code = ? AND Flare_Type = "1_hour"
                            GROUP BY Flare_Code) as latest_records'), function ($join) {
                $join->on('t.Flare_Code', '=', 'latest_records.Flare_Code')
                    ->on('t.CreateTime', '=', 'latest_records.latest_time');
            })
            ->addBinding($factoryCode, 'select')
            ->where('t.Factory_Code', $factoryCode)
            ->whereLike('t.CreateTime', $request->createTime.'%')
            ->orderBy('t.CreateTime', 'DESC')
            ->get();
        
        if(!$result1 && !$result2){
            return response()->json(['statuscode' => '204', 'status' => '查無資料', 'data' => []]);
        }

        $result = $result1->merge($result2);

        $result = collect($result);
        $sql = $result->map(function($item) {
            $itemArray = (array) $item;
            $itemArray['Flare_Data'] = json_decode($item->Flare_Data, true); 
            return $itemArray;
        });

        $groupedData = $sql->groupBy('Flare_Code')
                        ->map(function ($entries, $flareCode) {
                            $firstEntry = $entries->first();

                            $grouped = [
                                "Factory_Code" => $firstEntry['Factory_Code'],
                                "Flare_Code" => $flareCode,
                                "Flare_Data" => [],
                                "CreateTime" => $entries->max('CreateTime'),
                                "latest_time" => $entries->max('latest_time'),
                            ];

                            $flareData = [];
                            foreach ($entries as $entry) {
                                foreach ($entry['Flare_Data'] as $item) {
                                    $type = $item['type'] ?: "default"; // 如果 type ==空，用 "default" 表示
                                    if (!isset($flareData[$type])) {
                                        $flareData[$type] = ["unit" => $item['unit']];
                                    }
                                    $flareData[$type][$entry['Flare_Type']] = $item['value'];
                                }
                            }

                            $grouped['Flare_Data'] = collect($flareData)->map(function ($data) {
                                // 確保顺序＝ 15_min、1_hour、unit
                                return collect($data)
                                    ->sortBy(function ($value, $key) {
                                        $order = ['15_min', '1_hour', 'unit'];
                                        return array_search($key, $order) ?? PHP_INT_MAX;
                                    })
                                    ->toArray();
                            })->toArray();

                            return $grouped;

                        })
                        ->sortBy('Flare_Code')->values();

        return response()->json(['statuscode' => '200', 'status' => '資料查詢成功', 'data' => $groupedData]);
    }

}
