<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ModelsHolidayInnResortVanuatuSubPage extends Model
{
    protected $table = "holiday_inn_resort_vanuatu_sub_page";

    protected $fillable = [
        'id',
        'ref_id',
        'slug',
        'title',
        'image',
        'body',
    ];
}
