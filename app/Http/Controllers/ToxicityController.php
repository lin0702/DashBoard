<?php

namespace App\Http\Controllers;

use App\Models\Toxic;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Validation\Factory as ValidatorFactory;

class ToxicityController extends Controller
{
    protected $validatorFactory;

    public function __construct(ValidatorFactory $validatorFactory)
    {
        $this->validatorFactory = $validatorFactory;
    }

    public function createtoxicity(Request $request){
        // 
        $rules = [
            'date' => 'required|date', //日期
            'data' => 'required' //資料
        ];

        $validator = $this->validatorFactory->make($request->json()->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['statuscode' => '412', 'status' => '必要資料未帶入']);
        }

        $collection = collect($request->data)->transform(function($item, $key){
            return [
                'name' => $item['name'],
                'value' => round($item['value'],3)
            ];
        });

        $data = [
            'Tocic_Date' => Carbon::parse($request->date)->format('Y-m-d'),
            'Tocic_Data' => json_encode($collection, JSON_UNESCAPED_UNICODE)
        ];

        $find = Toxic::where('Tocic_Date',$data['Tocic_Date'])->exists();

        if(!$find){
            $sql = Toxic::insert($data);

            if($sql){
                return response()->json(['statuscode' => '200', 'status' => '資料新增成功']);
            }else{
                return response()->json(['statuscode' => '417', 'status' => '資料新增失敗']);
            }
        }else{
            return response()->json(['statuscode' => '417', 'status' => '該日已有資料無法重複新增']);
        }
    }

    public function updatetoxicity(Request $request){
        // 
        $rules = [
            'toxic_id' => 'required'
        ];

        $validator = $this->validatorFactory->make($request->json()->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['statuscode' => '412', 'status' => '必要資料未帶入']);
        }

        $data = [
            'Toxic_Id' => $request->toxic_id
        ];
        if($request->has('date')){
            $data['Tocic_Date'] = Carbon::parse($request->date)->format('Y-m-d');
        }

        if($request->has('data')){
            $collection = collect($request->data)->transform(function($item, $key){
                return [
                    'name' => $item['name'],
                    'value' => round($item['value'],3)
                ];
            });
            $data['Tocic_Data'] = json_encode($collection, JSON_UNESCAPED_UNICODE);
        }

        // 檢查是否存在完全相符的資料
        $exists = Toxic::where($data)->exists();

        if ($exists) {
            return response()->json(['statuscode' => '200', 'status' => '資料更新成功']);
        }else{
            // 如果不存在，儲存資料
            Arr::except($data, ['Toxic_Id']);

            $sql = Toxic::where('Toxic_Id',$request->toxic_id)->update($data);
            if($sql){
                return response()->json(['statuscode' => '200', 'status' => '資料更新成功']);
            }else{
                return response()->json(['statuscode' => '417', 'status' => '資料更新失敗']);
            }
        }
    }

    // GIS當日跟單日歷史紀錄用同一隻
    public function toxicity(Request $request){
        // 
        $rules = [
            'startdate' => 'required|date', //日期
        ];

        $validator = $this->validatorFactory->make($request->json()->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['statuscode' => '412', 'status' => '必要資料未帶入']);
        }

        $date = Carbon::parse($request->startdate)->format('Y-m-d');
        $sql = Toxic::where('Tocic_Date',$date)->first();

        if($sql){
            $sql->Tocic_Data = json_decode($sql->Tocic_Data, true);
            return response()->json(['statuscode' => '200', 'status' => '資料查詢成功', 'data' => $sql]);
        }else{
            return response()->json(['statuscode' => '204', 'status' => '資料查詢失敗或無資料', 'data' => []]);
        }
    }

    public function historymonthtoxicity(Request $request){
        // 
        $rules = [
            'startdate' => 'required|date', //日期
            'enddate' => 'required|date', //日期
            'toxicity' => 'required'
        ];

        $validator = $this->validatorFactory->make($request->json()->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['statuscode' => '412', 'status' => '必要資料未帶入']);
        }

        $sql = Toxic::whereBetween('Tocic_Date',[Carbon::parse($request->startdate)->format('Y-m-d'),Carbon::parse($request->enddate)->format('Y-m-d')])->get();
        if(!$sql){
            return response()->json(['statuscode' => '204', 'status' => '資料查詢失敗或無資料', 'data' => []]);
        }

        $key = $request->toxicity;
        $reslut = $sql->map(function($item) use ($key){
            $item['Tocic_Data'] = json_decode($item['Tocic_Data'],true);
            $filtered = collect($item['Tocic_Data'])->map(function ($item) use ($key){
                if($item['name'] == $key){
                    return $item['value'];
                }
            })->filter();

            if(!is_null($filtered->first())|| $filtered->first() != 0){
                return [
                    'Tocic_Date' => $item['Tocic_Date'],
                    'Tocic_Data' => $filtered->first()
                ];
            }
        })->filter()->values();

        $list = [
            'time' => $reslut->pluck('Tocic_Date'),
            'data' => $reslut->pluck('Tocic_Data')
        ];

        return response()->json(['statuscode' => '200', 'status' => '資料查詢成功', 'data' => $list]);
    }
}
