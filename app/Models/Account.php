<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'User_Id', 
        'Account', 
        'Password', 
        'Revoked',
        'CreateTime'
    ];

    protected $primaryKey = 'User_Id';
    protected $table = 'User';
    public $timestamps = false;
}
