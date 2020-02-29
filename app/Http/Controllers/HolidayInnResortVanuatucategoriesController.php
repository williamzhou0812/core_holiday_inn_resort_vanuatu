<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\HolidayInnResortVanuatuCategory;

class HolidayInnResortVanuatucategoriesController extends Controller
{
    //
    public function HolidayInnResortVanuatuCategories() {
        return response()->json(HolidayInnResortVanuatuCategory::get(), 200);
    }
}
