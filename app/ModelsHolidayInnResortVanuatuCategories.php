<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ModelsHolidayInnResortVanuatuCategories extends Model
{
    protected $table = "holiday_inn_resort_vanuatu_categories";

    protected $fillable = [
        'id',
        'slug',
        'title',
        'image',
    ];
}
