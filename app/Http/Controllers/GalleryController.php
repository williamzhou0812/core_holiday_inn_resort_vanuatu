<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Gallery;

class GalleryController extends Controller
{
    //
    public function Gallery() {
        return response()->json(Gallery::get(), 200);
    }
}
