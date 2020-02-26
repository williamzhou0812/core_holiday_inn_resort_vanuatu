<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ModelsFoodAndDiningOut extends Model
{
    protected $table = "food_and_dining_out";

    protected $fillable = [
        'id',
        'title',
        'sub_title',
        'image',
        'body',
    ];
}
