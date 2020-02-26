<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ModelsGallery extends Model
{
     //
     protected $table = "gallery";

     protected $fillable = [
         'id',
         'slug',
         'title',
         'gallery_images',
     ];
}
