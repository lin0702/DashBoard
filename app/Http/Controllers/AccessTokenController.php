<?php

namespace App\Http\Controllers;

use Laravel\Passport\Http\Controllers\AccessTokenController as PassportAccessTokenController;
use Carbon\Carbon;
use App\Models\Account;
use App\Models\OauthAccessTokens;
use Psr\Http\Message\ServerRequestInterface;
use Illuminate\Foundation\Validation\ValidatesRequests;

class AccessTokenController extends PassportAccessTokenController
{
    use ValidatesRequests;

    public function issueToken(ServerRequestInterface  $request){
        $parms = $request->getParsedBody();
        if ($parms['account'] && $parms['password']) {
            $sql = Account::where('Account', $parms['account'])->first();

            if (!$sql) {
                return response()->json(['statuscode' => '400', 'status' => '查無此帳號請']);
            }
            if (!password_verify($parms['password'], $sql->Password)) {
                return response()->json(['statuscode' => '400', 'status' => '密碼錯誤']);
            }
        } else {
            return response()->json(['statuscode' => '412', 'status' => '必要資料未帶入']);
        }

        if ($sql->Revoked == 1) {
            return response()->json(['statuscode' => '400', 'status' => '帳號已停權']);
        }

        $uuid = bin2hex(random_bytes(128)); // 生成一个随机 UUID
        $hash = hash('sha256', $uuid);
        $createtime = Carbon::now()->format('Y-m-d H:i:s');
        $expirestime = Carbon::now()->addSeconds(3600)->format('Y-m-d H:i:s'); //設定有效期限為1小時

        $data = [
            "Token" => $hash,
            "User_Id" => $sql->User_Id,
            "Created_at" => $createtime,
            "Expires_at" => $expirestime
        ];

        $hasuser = OauthAccessTokens::where('ID', $sql->User_Id)->count();

        if ($hasuser == 0) {
            $newsql = OauthAccessTokens::insert($data);
        } else {
            $newsql = OauthAccessTokens::where('ID', $sql->User_Id)->update($data);
        }

        // 检查 access_token 是否存在
        if ($newsql) {
            $data = [
                'User_Id' => $sql->User_Id,
                'Account' => $parms['account'],
                'token_type' => 'Bearer',
                'Token' => $uuid
            ];

            return response()->json(['statuscode' => '200', 'status' => '登入成功', 'data' => $data]);
        } else {
            return response()->json(['statuscode' => '417', 'status' => 'Access token not found']);
        }
    }
}
