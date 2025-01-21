<?php

namespace App\Http\Controllers;

use App\Models\Aloha;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Factory as ValidatorFactory;

class AlohaController extends Controller
{
    protected $validatorFactory;

    public function __construct(ValidatorFactory $validatorFactory)
    {
        $this->validatorFactory = $validatorFactory;
    }

    public function insertdata(Request $request){
        $rules = [
            'KML' => 'required',
            'summary' => 'required|string|max:4294967295',
            'longitude' => 'required|numeric|between:120,122',
            'latitude' => 'required|numeric|between:22,25'
        ];

        $validator = $this->validatorFactory->make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['statuscode' => '412', 'status' => '必要資料未帶入或是資料錯誤']);
        }

        $data = [
            'Summary' => $request->summary,
            'Longitude' => $request->longitude,
            'Latitude' => $request->latitude,
        ];
        
        $file = $request->file('KML');
        if($file->getClientOriginalExtension() != 'kml'){
            return response()->json(['statuscode' => '412', 'status' => 'kml檔案格式錯誤']);
        }
        $originalFileName = $file->getClientOriginalName(); // 原始名稱
        $domain = $request->getSchemeAndHttpHost(); 
        $filePath = $file->storeAs('KML', $originalFileName); // 儲存到指定資料夾並保留名稱
        $data['KML'] = $domain.Storage::url($filePath);

        if($request->has('windSpeed')){
            $data['Wind_Speed'] = $request->windSpeed;
        }

        if($request->has('windDirection')){
            $data['Wind_Direction'] = $request->windDirection;
        }


        $sql = Aloha::create($data);

        if($sql){
            return response()->json(['statuscode' => '200', 'status' => '資料新增成功']);
        }else{
            return response()->json(['statuscode' => '417', 'status' => '資料新增失敗']);
        }
    }

    public function selectdata(){
        $sql = Aloha::orderby('CreaterTime', 'desc')->first();

        if(!$sql){
            return response()->json(['statuscode' => '204', 'status' => '資料查詢失敗或無資料', 'data' => []]);
        }else{
            // $sql->Summary = str_replace("\n", ' ', $sql->Summary); //確認是否要換行符號
            return response()->json(['statuscode' => '200', 'status' => '資料查詢成功', 'data' => $sql]);
        }
    }
}
