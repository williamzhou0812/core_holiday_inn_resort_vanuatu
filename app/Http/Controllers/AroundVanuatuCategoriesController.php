<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\AroundVanuatuCategory;

class AroundVanuatuCategoriesController extends Controller
{
    //
    public function AroundVanuatuCategories() {
        return response()->json(AroundVanuatuCategory::get(), 200);
    }
}
