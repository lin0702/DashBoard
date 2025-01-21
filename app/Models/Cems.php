<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cems extends Model
{
    use HasFactory;

    protected $fillable = [
        'CEMS_Id', 
        'Factory_Code', 
        'Factory_Code',
        'CEMS_Type',
        'CEMS_Data',
        'CreateTime'
    ];

    protected $primaryKey = 'CEMS_Id';
    protected $table = 'CEMS_event';
}
