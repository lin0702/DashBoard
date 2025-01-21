<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Volatility extends Model
{
    protected $fillable = [
        'Volatility_Id', 
        'Volatility_Date', 
        'Volatility_Data', 
        'CreateTime'
    ];

    protected $primaryKey = 'Volatility_Id';
    protected $table = 'Volatility_Data';
    public $timestamps = false;
}
