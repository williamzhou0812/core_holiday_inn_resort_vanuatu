<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ModelsAroundVanuatuCategoriesModel extends Model
{
    //
    protected $table = "around_vanuatu_categories";

    protected $fillable = [
        'id',
        'slug',
        'title',
        'image',
    ];
}
