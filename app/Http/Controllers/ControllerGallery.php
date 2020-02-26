<?php

namespace App\Http\Controllers;

use App\ModelsGallery;
use Illuminate\Http\Request;

class ControllerGallery extends Controller
{
    public function Gallery() {
        return response()->json(ModelsGallery::get(), 200);
    }
}
