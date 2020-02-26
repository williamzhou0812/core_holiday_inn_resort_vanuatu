<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ModelsRetailAndService extends Model
{
    protected $table = "retail_and_service";

    protected $fillable = [
        'id',
        'title',
        'sub_title',
        'image',
        'body',
    ];
}
