<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Validation\Factory as ValidatorFactory;

class UserController extends Controller
{
    protected $validatorFactory;

    public function __construct(ValidatorFactory $validatorFactory)
    {
        $this->validatorFactory = $validatorFactory;
    }

    public function createaccount(Request $request){
        $rules = [
            'account' => 'required',
            'password' => 'required'
        ];

        $validator = $this->validatorFactory->make($request->json()->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['statuscode' => '412', 'status' => '必要資料未帶入']);
        }

        $check = Account::where('Account', $request->account)->count();
        if($check > 0){
            return response()->json(['statuscode' => '204', 'status' => '帳號重複']);
        }

        $data = [
            'Account' => $request->account,
            'Password' => password_hash($request->password, PASSWORD_DEFAULT)
        ];

        $sql = Account::insert($data);
        if($sql){
            return response()->json(['statuscode' => '200', 'status' => '帳號新增成功']);
        }else{
            return response()->json(['statuscode' => '204', 'status' => '帳號新增失敗']);
        }
    }

    public function updateaccount(Request $request){
        $rules = [
            'accountId' => 'required'
        ];

        $validator = $this->validatorFactory->make($request->json()->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['statuscode' => '412', 'status' => '必要資料未帶入']);
        }

        $data['User_Id'] = $request->accountId;
        if($request->has('password')){
            $data['Password'] = $request->password;
        }

        if($request->has('revoked')){
            $data['Revoked'] = $request->revoked;
        }

        if($request->has('account')){
            $data['Account'] = $request->account;
        }

        // 檢查是否存在完全相符的資料
        $exists = Account::where($data)->exists();

        if ($exists) {
            return response()->json(['statuscode' => '200', 'status' => '資料更新成功']);
        }else{
            // 如果不存在，儲存資料
            Arr::except($data, ['User_Id']);

            $sql = Account::where('User_Id',$request->accountId)->update($data);
            if($sql){
                return response()->json(['statuscode' => '200', 'status' => '資料更新成功']);
            }else{
                return response()->json(['statuscode' => '417', 'status' => '資料更新失敗']);
            }
        }
    }

    public function selectaccount(){
        $sql = Account::orderby('CreateTime', 'desc')->get([
            'User_Id','Account','Revoked'
        ]);

        $sql = $sql->map(function($item){
            $item['Revoked'] = $item['Revoked'] == 0 ? '啟用' : '停用';
            return [
                'User_Id' => $item['User_Id'],
                'Account' => $item['Account'],
                'Revoked' => $item['Revoked']
            ];
        });

        if(!$sql){
            return response()->json(['statuscode' => '204', 'status' => '資料查詢失敗或無資料', 'data' => []]);
        }else{
            return response()->json(['statuscode' => '200', 'status' => '資料查詢成功', 'data' => $sql]);
        }
    }

    public function oneaccount(Request $request){
        $rules = [
            'accountId' => 'required'
        ];

        $validator = $this->validatorFactory->make($request->json()->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['statuscode' => '412', 'status' => '必要資料未帶入']);
        }

        $sql = Account::where('User_Id', $request->accountId)->first();

        $sql->Revoked == 0 ? '啟用' : '停用';

        if(!$sql){
            return response()->json(['statuscode' => '204', 'status' => '資料查詢失敗或無資料', 'data' => []]);
        }else{
            return response()->json(['statuscode' => '200', 'status' => '資料查詢成功', 'data' => $sql]);
        }
    }
}
