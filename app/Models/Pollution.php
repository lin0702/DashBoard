<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pollution extends Model
{
    protected $fillable = [
        'Pollution_Id', 
        'Pollution_Date', 
        'Pollution_Data', 
        'CreateTime'
    ];

    protected $primaryKey = 'Pollution_Id';
    protected $table = 'Pollution_Data';
    public $timestamps = false;
}
