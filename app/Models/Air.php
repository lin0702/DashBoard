<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Air extends Model
{
    use HasFactory;

    protected $fillable = [
        'Air_Id', 
        'Air_Data',
        'CreateTime'
    ];

    protected $primaryKey = 'Air_Id';
    protected $table = 'Air_event';
}
