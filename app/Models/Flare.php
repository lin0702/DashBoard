<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Flare extends Model
{
    use HasFactory;

    protected $fillable = [
        'Flare_Id', 
        'Flare_Type', 
        'Factory_Code', 
        'Flare_Code',
        'Flare_Data',
        'CreateTime'
    ];

    protected $primaryKey = 'Flare_Id';
    protected $table = 'Flare_event';
}
