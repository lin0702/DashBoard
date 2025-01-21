<?php

namespace App\Http\Controllers;

use App\Models\Pollution;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Validation\Factory as ValidatorFactory;

class PollutionController extends Controller
{
    protected $validatorFactory;

    public function __construct(ValidatorFactory $validatorFactory)
    {
        $this->validatorFactory = $validatorFactory;
    }

    public function createpollution(Request $request){
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
            'Pollution_Date' => Carbon::parse($request->date)->format('Y-m-d'),
            'Pollution_Data' => json_encode($collection, JSON_UNESCAPED_UNICODE)
        ];

        $find = Pollution::where('Pollution_Date',$data['Pollution_Date'])->exists();

        if(!$find){
            $sql = Pollution::insert($data);

            if($sql){
                return response()->json(['statuscode' => '200', 'status' => '資料新增成功']);
            }else{
                return response()->json(['statuscode' => '417', 'status' => '資料新增失敗']);
            }
        }else{
            return response()->json(['statuscode' => '417', 'status' => '該日已有資料無法重複新增']);
        }
    }

    public function updatepollution(Request $request){
        $rules = [
            'pollution_id' => 'required'
        ];

        $validator = $this->validatorFactory->make($request->json()->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['statuscode' => '412', 'status' => '必要資料未帶入']);
        }

        $data = [
            'Pollution_id' => $request->pollution_id
        ];
        if($request->has('date')){
            $data['Pollution_Date'] = Carbon::parse($request->date)->format('Y-m-d');
        }

        if($request->has('data')){
            $collection = collect($request->data)->transform(function($item, $key){
                return [
                    'name' => $item['name'],
                    'value' => round($item['value'],3)
                ];
            });
            $data['Pollution_Data'] = json_encode($collection, JSON_UNESCAPED_UNICODE);
        }

        // 檢查是否存在完全相符的資料
        $exists = Pollution::where($data)->exists();

        if ($exists) {
            return response()->json(['statuscode' => '200', 'status' => '資料更新成功']);
        }else{
            // 如果不存在，儲存資料
            Arr::except($data, ['Pollution_Id']);

            $sql = Pollution::where('Pollution_Id',$request->pollution_id)->update($data);
            if($sql){
                return response()->json(['statuscode' => '200', 'status' => '資料更新成功']);
            }else{
                return response()->json(['statuscode' => '417', 'status' => '資料更新失敗']);
            }
        }
    }

    public function pollution(Request $request){
        $rules = [
            'startdate' => 'required|date', //日期
        ];

        $validator = $this->validatorFactory->make($request->json()->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['statuscode' => '412', 'status' => '必要資料未帶入']);
        }

        $date = Carbon::parse($request->startdate)->format('Y-m-d');
        $sql = Pollution::where('Pollution_Date',$date)->first();

        if($sql){
            $sql->Pollution_Data = json_decode($sql->Pollution_Data, true);
            return response()->json(['statuscode' => '200', 'status' => '資料查詢成功', 'data' => $sql]);
        }else{
            return response()->json(['statuscode' => '204', 'status' => '資料查詢失敗或無資料', 'data' => []]);
        }
    }

    public function historymonthpollution(Request $request){
        $rules = [
            'startdate' => 'required|date', //日期
            'enddate' => 'required|date', //日期
            'pollution' => 'required'
        ];

        $validator = $this->validatorFactory->make($request->json()->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['statuscode' => '412', 'status' => '必要資料未帶入']);
        }

        $sql = Pollution::whereBetween('Pollution_Date',[Carbon::parse($request->startdate)->format('Y-m-d'),Carbon::parse($request->enddate)->format('Y-m-d')])->get();
        if(!$sql){
            return response()->json(['statuscode' => '204', 'status' => '資料查詢失敗或無資料', 'data' => []]);
        }

        $key = $request->pollution;
        $reslut = $sql->map(function($item) use ($key){
            $item['Pollution_Data'] = json_decode($item['Pollution_Data'],true);
            $filtered = collect($item['Pollution_Data'])->map(function ($item) use ($key){
                if($item['name'] == $key){
                    return $item['value'];
                }
            })->filter();

            if(!is_null($filtered->first())|| $filtered->first() != 0){
                return [
                    'Pollution_Date' => $item['Pollution_Date'],
                    'Pollution_Data' => $filtered->first()
                ];
            }
        })->filter()->values();

        $list = [
            'time' => $reslut->pluck('Pollution_Date'),
            'data' => $reslut->pluck('Pollution_Data')
        ];

        return response()->json(['statuscode' => '200', 'status' => '資料查詢成功', 'data' => $list]);
    }
}
