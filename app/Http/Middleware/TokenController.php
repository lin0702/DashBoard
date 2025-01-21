<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\OauthAccessTokens;
use App\Models\Account;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TokenController
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 取得Authorization Header
        $authHeader = $request->header('Authorization');
        // 确保 Authorization header 存在並且是 'Bearer'
        if (!$authHeader || strpos($authHeader, 'Bearer ') !== 0) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        // 移除 'Bearer ' 前缀
        $accessToken = substr($authHeader, 7);
        // 检查 token 是否存在并且没有過期
        $token = OauthAccessTokens::where('token', hash('sha256', $accessToken))->first();
        if (!$token || Carbon::parse($token->expires_at)->isPast()) {
            return response()->json(['statuscode' => '400', 'status' => '請重新登入']);
        }
        if ($token->User_Id != (int)$request->user_id) {
            return response()->json(['statuscode' => '400', 'status' => 'Token與帳號不符合請重新登入']);
        }
        $permissions = Account::where('User_Id', $request->user_id)->where('revoked', 0)->first();
        if ($permissions) {
            $request->merge(['permissions' => $permissions->Permissions]);
            return $next($request);
        } else {
            return response()->json(['statuscode' => '400', 'status' => '帳號已停權']);
        }
    }
}
