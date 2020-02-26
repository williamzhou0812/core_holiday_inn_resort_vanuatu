<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ModelsRestaurant extends Model
{
    //
    protected $table = "restaurants";

    protected $fillable = [
        'id',
        'slug',
        'title',
        'image',
        'menu',
        'menu_link',
        'body',
        
    ];
}
