<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ModelsNav extends Model
{
    
    //
    protected $table = "navs";

    protected $fillable = [
        'id',
        'title',
        'slug',
    ];
}
