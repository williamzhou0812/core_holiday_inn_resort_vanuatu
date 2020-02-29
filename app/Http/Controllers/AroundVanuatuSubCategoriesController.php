<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\AroundVanuatuSubCategory;

class AroundVanuatuSubCategoriesController extends Controller
{
    //
    public function AroundVanuatuSubCategories() {
        return response()->json(AroundVanuatuSubCategory::get(), 200);
    }
}
