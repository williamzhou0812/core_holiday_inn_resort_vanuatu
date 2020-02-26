<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ModelsPostsModel extends Model
{
    //
    protected $table = "posts";

    protected $fillable = [
        'id',
        'author_id',
        'title',
        'body'
    ];
}
