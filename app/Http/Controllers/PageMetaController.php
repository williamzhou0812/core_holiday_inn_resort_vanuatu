<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\PageMetum;

class PageMetaController extends Controller
{
    //
    public function PageMeta() {
        return response()->json(PageMetum::get(), 200);
    }
}
