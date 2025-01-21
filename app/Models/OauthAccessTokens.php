<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OauthAccessTokens extends Model
{
    protected $table = 'oauth_access_tokens';
    public $timestamps = false;
    protected $primaryKey = 'ID'; // 指定主鍵名稱
    protected $fillable = [
        'User_Id',
        'Token',
        'Created_at', //建立時間
        'Expires_at' //過期時間
    ];
}
