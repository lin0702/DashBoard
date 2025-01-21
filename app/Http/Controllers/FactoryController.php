<?php

namespace App\Http\Controllers;

use App\Models\Factory;
use App\Models\Material;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Factory as ValidatorFactory;

use function PHPUnit\Framework\isNull;

class FactoryController extends Controller
{
    protected $validatorFactory;

    public function __construct(ValidatorFactory $validatorFactory)
    {
        $this->validatorFactory = $validatorFactory;
    }

    // 新增單筆廠區
    public function createfactory(Request $request){
        // 
        $rules = [
            'factoryName' => 'required', //廠區名稱
            'factoryCode' => 'required', //管制編號
            'coordinate' => 'required', //經緯度
            'contactPerson' => 'required' //聯絡人資料
        ];

        $validator = $this->validatorFactory->make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['statuscode' => '412', 'status' => '必要資料未帶入']);
        }

        $data = [
            'Location_Name' => $request->factoryName,
            'Control_Id' => $request->factoryCode,
            'Coordinate' => $request->coordinate,
            'Contact_Person' => $request->contactPerson //json_string
        ];

        if($request->hasFile('factoryMap')){
            $file = $request->file('factoryMap');
            $domain = $request->getSchemeAndHttpHost(); 
            $originalFileName = $file->getClientOriginalName(); // 原始名稱
            $filePath = $file->storeAs('factoryMap', $originalFileName); // 儲存到指定資料夾並保留名稱
            $data['Factory_Map'] = $domain.Storage::url($filePath);
        }

        if($request->hasFile('equipment')){
            $file = $request->file('equipment');
            $domain = $request->getSchemeAndHttpHost(); 
            $originalFileName = $file->getClientOriginalName(); // 原始名稱
            $filePath = $file->storeAs('equipment', $originalFileName); // 儲存到指定資料夾並保留名稱
            $data['Equipment_Diagram'] = $domain.Storage::url($filePath);
        }

        if($request->has('chemicals')){
            $data['Chemicals'] = $request->chemicals; //json_string
        }

        if($request->has('dangerous')){
            $data['Dangerous'] = $request->dangerous; //json_string
        }

        if($request->hasFile('picture')){
            $file = $request->file('picture');
            $originalFileName = $file->getClientOriginalName(); // 原始名稱
            $filePath = $file->storeAs('360pic', $originalFileName); // 儲存到指定資料夾並保留名稱
            $data['Picture'] = Storage::url($filePath);;
        }

        $sql = Factory::insert($data);

        if($sql){
            return response()->json(['statuscode' => '200', 'status' => '資料新增成功']);
        }else{
            return response()->json(['statuscode' => '417', 'status' => '資料新增失敗']);
        }
    }

    public function updatefactory(Request $request){
        $rules = [
            'factoryId' => 'required', //廠區PK
        ];

        $validator = $this->validatorFactory->make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['statuscode' => '412', 'status' => '必要資料未帶入']);
        }

        $olddata = Factory::where('Location_Id',$request->factoryId)->first();

        if($request->has('factoryName')){
            $data['Location_Name'] = $request->factoryName; 
        }

        if($request->has('factoryCode')){
            $data['Control_Id'] = $request->factoryCode; 
        }

        if($request->has('coordinate')){
            $data['Coordinate'] = $request->coordinate; 
        }

        if($request->has('contactPerson')){
            $data['Contact_Person'] = $request->contactPerson; 
        }

        if($request->hasFile('factoryMap')){
            $file = $request->file('factoryMap');
            $originalFileName = $file->getClientOriginalName(); // 原始名稱

            $oldFilePath = $olddata->Factory_Map;
            // 刪除舊圖片（如果存在）
            if (!empty($oldFilePath) && Storage::exists($oldFilePath)) {
                Storage::delete($oldFilePath);
            }

            $domain = $request->getSchemeAndHttpHost(); 
            $filePath = $file->storeAs('factoryMap', $originalFileName); // 儲存到指定資料夾並保留名稱
            $data['Factory_Map'] = $domain.Storage::url($filePath);;
        }

        if($request->hasFile('equipment')){
            $file = $request->file('equipment');
            $originalFileName = $file->getClientOriginalName(); // 原始名稱

            $oldFilePath = $olddata->Equipment_Diagram;
            // 刪除舊圖片（如果存在）
            if (!empty($oldFilePath) && Storage::exists($oldFilePath)) {
                Storage::delete($oldFilePath);
            }

            $domain = $request->getSchemeAndHttpHost(); 
            $filePath = $file->storeAs('equipment', $originalFileName); // 儲存到指定資料夾並保留名稱
            $data['Equipment_Diagram'] = $domain.Storage::url($filePath);;
        }

        if($request->has('chemicals')){
            $data['Chemicals'] = $request->chemicals; //json_string
        }

        if($request->has('dangerous')){
            $data['Dangerous'] = $request->dangerous; //json_string
        }

        if($request->hasFile('picture')){
            $file = $request->file('picture');
            $originalFileName = $file->getClientOriginalName(); // 原始名稱

            $oldFilePath = $olddata->Picture;
            // 刪除舊圖片（如果存在）
            if (!empty($oldFilePath) && Storage::exists($oldFilePath)) {
                Storage::delete($oldFilePath);
            }

            $filePath = $file->storeAs('360pic', $originalFileName); // 儲存到指定資料夾並保留名稱
            $data['Picture'] = Storage::url($filePath);;
        }

        $data['Location_Id'] = $request->factoryId;

        // 檢查是否存在完全相符的資料
        $exists = Factory::where($data)->exists();

        if ($exists) {
            return response()->json(['statuscode' => '200', 'status' => '資料更新成功']);
        }else{
            // 如果不存在，儲存資料
            Arr::except($data, ['Location_Id']);

            $sql = Factory::where('Location_Id',$request->factoryId)->update($data);
            if($sql){
                return response()->json(['statuscode' => '200', 'status' => '資料更新成功']);
            }else{
                return response()->json(['statuscode' => '417', 'status' => '資料更新失敗']);
            }
        }
        
    }

    public function allfactories(Request $request){
        // 
        $sql = Factory::get(['Location_Id', 'Location_Name', 'Control_Id', 'Coordinate', 'Factory_Map', 'Contact_Person']);
        $sql = $sql->map(function($item){
            return [
                'Location_Id' => $item['Location_Id'],
                'Location_Name' => $item['Location_Name'],
                'Control_Id' => $item['Control_Id'],
                'Coordinate' => $item['Coordinate'],
                'Factory_Map' => $item['Factory_Map'],
                'Contact_Person' => json_decode($item['Contact_Person'], true)
            ];
        });

        if($sql){
            return response()->json(['statuscode' => '204', 'status' => '資料查詢失敗或無資料', 'data' => $sql]);
        }else{
            return response()->json(['statuscode' => '200', 'status' => '資料查詢成功', 'data' => []]);
        }

    }

    public function factories(Request $request){
        // 
        $sql = Factory::get();
        $sql = $sql->map(function($item){
            if(($item['Chemicals']) != ''){
                $chemicals = Arr::pluck(json_decode($item['Chemicals'], true), 'name');
                $material = Material::whereIn('Material_Name',$chemicals)->get();
                $merged = collect(json_decode($item['Chemicals'],true))->keyBy('name');
                collect($material)->each(function ($item) use ($merged){
                    if ($merged->has($item['Material_Name'])){
                        $merged[$item['name']] = collect($merged[$item['Material_Name']])->put('Url', $item['Hazard_Url']);
                    }
                });

                $filtered = $merged->filter(function ($item) {
                    return isset($item['Url']);
                });

                return [
                    'Location_Id' => $item['Location_Id'],
                    'Location_Name' => $item['Location_Name'],
                    'Control_Id' => $item['Control_Id'],
                    'Coordinate' => $item['Coordinate'],
                    'Factory_Map' => $item['Factory_Map'],
                    'Contact_Person' => json_decode($item['Contact_Person'], true),
                    'Equipment_Diagram' => $item['Equipment_Diagram'],
                    'Chemicals' => $filtered,
                    'Dangerous' => json_decode($item['Dangerous'], true),
                    'Picture' => $item['Picture']
                ];
            }else{
                return [
                    'Location_Id' => $item['Location_Id'],
                    'Location_Name' => $item['Location_Name'],
                    'Control_Id' => $item['Control_Id'],
                    'Coordinate' => $item['Coordinate'],
                    'Factory_Map' => $item['Factory_Map'],
                    'Contact_Person' => json_decode($item['Contact_Person'], true),
                    'Equipment_Diagram' => $item['Equipment_Diagram'],
                    'Chemicals' => json_decode($item['Chemicals'], true),
                    'Dangerous' => json_decode($item['Dangerous'], true),
                    'Picture' => $item['Picture']
                ];
            }
        });

        return $sql;

        if($sql){
            return response()->json(['statuscode' => '204', 'status' => '資料查詢失敗或無資料', 'data' => $sql]);
        }else{
            return response()->json(['statuscode' => '200', 'status' => '資料查詢成功', 'data' => []]);
        }

    }

    public function factory(Request $request){
        // 
        $rules = [
            'factoryId' => 'required', //廠區PK
        ];

        $validator = $this->validatorFactory->make($request->json()->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['statuscode' => '412', 'status' => '必要資料未帶入']);
        }

        $factory = Factory::where('Location_Id', $request->factoryId)->first();

        if(!$factory){
            return response()->json(['statuscode' => '204', 'status' => '資料查詢失敗或無資料', 'data' => []]);
        }elseif(is_null($factory->Chemicals) || $factory->Chemicals == 'null' || $factory->Chemicals == "NULL"){
            return response()->json(['statuscode' => '200', 'status' => '資料查詢成功', 'data' => $factory]);
        }

        $chemicals = Arr::pluck(json_decode($factory->Chemicals,true), 'name');

        if(count($chemicals) != 0){
            $material = Material::whereIn('Material_Name',$chemicals)->get();

            if(!$material){
                return response()->json(['statuscode' => '204', 'status' => '資料查詢失敗或無資料', 'data' => []]);
            }

            if($factory->Chemicals != ''){
                $merged = collect(json_decode($factory->Chemicals,true))->keyBy('name');
                collect($material)->each(function ($item) use ($merged){
                    if ($merged->has($item['Material_Name'])){
                        $merged[$item['name']] = collect($merged[$item['Material_Name']])->put('Url', $item['Hazard_Url']);
                    }
                });

                $filtered = $merged->filter(function ($item) {
                    return isset($item['Url']);
                });

                $factory->Chemicals = $filtered;
            }

            if($factory->Contact_Person != ''){
                $factory->Contact_Person = json_decode($factory->Contact_Person, true);
            }
            if($factory->Dangerous != ''){
                $factory->Dangerous = json_decode($factory->Dangerous, true);
            }


            return response()->json(['statuscode' => '200', 'status' => '資料查詢成功', 'data' => $factory]);

        }else{
            return response()->json(['statuscode' => '200', 'status' => '資料查詢成功', 'data' => []]);
        }
        
    }
}
