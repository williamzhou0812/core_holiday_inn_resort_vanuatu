<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ModelsPageMeta extends Model
{
    //
    protected $table = "page_meta";

    protected $fillable = [
        'id',
        'site_header_image',
        'site_title',
        'site_footer',
        'site_logo',
        'current_datatime',
    ];

}
