<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Opacity extends Model
{
    use HasFactory;

    protected $fillable = [
        'Opacity_Id', 
        'Factory_Code', 
        'Opacity_Code', 
        'Opacity_data',
        'CreateTime'
    ];

    protected $primaryKey = 'Opacity_Id';
    protected $table = 'Opacity_event';
}
