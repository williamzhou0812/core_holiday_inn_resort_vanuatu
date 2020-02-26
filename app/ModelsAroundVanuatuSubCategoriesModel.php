<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ModelsAroundVanuatuSubCategoriesModel extends Model
{
    protected $table = "around_vanuatu_sub_categories";

    protected $fillable = [
        'id',
        'ref_categories_id',
        'slug',
        'title',
        'image',
        'body',
    ];
}
