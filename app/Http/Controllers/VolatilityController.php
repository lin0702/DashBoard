<?php

namespace App\Http\Controllers;

use App\Models\Volatility;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Validation\Factory as ValidatorFactory;

class VolatilityController extends Controller
{
    protected $validatorFactory;

    public function __construct(ValidatorFactory $validatorFactory)
    {
        $this->validatorFactory = $validatorFactory;
    }

    public function createvolatility(Request $request){
        $rules = [
            'date' => 'required|date', //日期
            'data' => 'required' //資料
        ];

        $validator = $this->validatorFactory->make($request->json()->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['statuscode' => '412', 'status' => '必要資料未帶入']);
        }

        $data = [
            'Volatility_Date' => Carbon::parse($request->date)->format('Y-m-d'),
            'Volatility_Data' => json_encode($request->data, JSON_UNESCAPED_UNICODE)
        ];

        $find = Volatility::where('Volatility_Date',$data['Volatility_Date'])->exists();

        if(!$find){
            $sql = Volatility::insert($data);

            if($sql){
                return response()->json(['statuscode' => '200', 'status' => '資料新增成功']);
            }else{
                return response()->json(['statuscode' => '417', 'status' => '資料新增失敗']);
            }
        }else{
            return response()->json(['statuscode' => '417', 'status' => '該日已有資料無法重複新增']);
        }
    }

    public function updatevolatility(Request $request){
        $rules = [
            'volatility_id' => 'required'
        ];

        $validator = $this->validatorFactory->make($request->json()->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['statuscode' => '412', 'status' => '必要資料未帶入']);
        }

        $data = [
            'Volatility_id' => $request->volatility_id
        ];
        if($request->has('date')){
            $data['Volatility_Date'] = Carbon::parse($request->date)->format('Y-m-d');
        }

        if($request->has('data')){
            $data['Volatility_Data'] = json_encode($request->data, JSON_UNESCAPED_UNICODE);
        }

        // 檢查是否存在完全相符的資料
        $exists = Volatility::where($data)->exists();

        if ($exists) {
            return response()->json(['statuscode' => '200', 'status' => '資料更新成功']);
        }else{
            // 如果不存在，儲存資料
            Arr::except($data, ['Volatility_id']);

            $sql = Volatility::where('Volatility_id',$request->volatility_id)->update($data);
            if($sql){
                return response()->json(['statuscode' => '200', 'status' => '資料更新成功']);
            }else{
                return response()->json(['statuscode' => '417', 'status' => '資料更新失敗']);
            }
        }
    }

    public function nowvolatility(Request $request){
        $rules = [
            'startdate' => 'required|date', //日期
        ];

        $validator = $this->validatorFactory->make($request->json()->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['statuscode' => '412', 'status' => '必要資料未帶入']);
        }

        $date = Carbon::parse($request->startdate)->format('Y-m-d');
        $sql = Volatility::where('Volatility_Date',$date)->first();

        if(!$sql){
            return response()->json(['statuscode' => '204', 'status' => '資料查詢失敗或無資料', 'data' => []]);
        }

        $datalist = collect(json_decode($sql->Volatility_Data, true));
        $time = $datalist->pluck('time');

        // $key = ['甲烷', '非甲烷', '總碳氫化合物'];
        // $volatility = collect($datalist->pluck('volatility'));
        // foreach($key as $vkey){
        //     $result = $volatility->map(function ($group) use ($vkey){
        //         return collect($group)->firstWhere('name', $vkey);
        //     });
        //     $result = $result->pluck('value');
        //     $data[$vkey]= $result;
        // }

        // $list = [
        //     'time' => $time,
        //     'data' => $data
        // ];
        return response()->json(['statuscode' => '200', 'status' => '資料查詢成功', 'data' => $datalist]);
    }

    public function volatility(Request $request){
        $rules = [
            'startdate' => 'required|date', //日期
        ];

        $validator = $this->validatorFactory->make($request->json()->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['statuscode' => '412', 'status' => '必要資料未帶入']);
        }

        $date = Carbon::parse($request->startdate)->format('Y-m-d');
        $sql = Volatility::where('Volatility_Date',$date)->first();

        if(!$sql){
            return response()->json(['statuscode' => '204', 'status' => '資料查詢失敗或無資料', 'data' => []]);
        }

        $datalist = collect(json_decode($sql->Volatility_Data, true));
        $time = $datalist->pluck('time');

        $key = ['甲烷', '非甲烷', '總碳氫化合物'];
        $volatility = collect($datalist->pluck('volatility'));
        foreach($key as $vkey){
            $result = $volatility->map(function ($group) use ($vkey){
                return collect($group)->firstWhere('name', $vkey);
            });
            $result = $result->pluck('value');
            $data[$vkey]= $result;
        }

        $list = [
            'time' => $time,
            'data' => $data
        ];
        return response()->json(['statuscode' => '200', 'status' => '資料查詢成功', 'data' => $list]);
    }

    public function historymonthvolatility(Request $request){
        $rules = [
            'startdate' => 'required|date', //日期
            'enddate' => 'required|date'
        ];

        $validator = $this->validatorFactory->make($request->json()->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['statuscode' => '412', 'status' => '必要資料未帶入']);
        }

        $sql = Volatility::whereBetween('Volatility_Date',[Carbon::parse($request->startdate)->format('Y-m-d'),Carbon::parse($request->enddate)->format('Y-m-d')])->get();
        if(!$sql){
            return response()->json(['statuscode' => '204', 'status' => '資料查詢失敗或無資料', 'data' => []]);
        }

        // 時間列表
        $time = $sql->flatMap(function($item, $key){
            $datalist = collect(json_decode($item['Volatility_Data'],true));
            $date = $item['Volatility_Date'];
            $timelist = $datalist->pluck('time')->map(function($titem) use ($date){
                return $date. ' '. $titem;
            });
            return $timelist;
        });

        // 資料列表
        $key = ['甲烷', '非甲烷', '總碳氫化合物'];
        foreach ($key as $vkey) {
            $data = $sql->flatMap(function($item, $key) use ($vkey){
                $datalist = collect(json_decode($item['Volatility_Data'],true))->pluck('volatility');
                $result = $datalist->flatMap(function ($group) use ($vkey){
                    return collect($group)->Where('name', $vkey)->pluck('value');
                });
                return $result;
            });

            $newdata[$vkey] = $data;
        }

        $list = [
            'time' => $time,
            'data' => $newdata
        ];
        
        return response()->json(['statuscode' => '200', 'status' => '資料查詢成功', 'data' => $list]);
    }
}
