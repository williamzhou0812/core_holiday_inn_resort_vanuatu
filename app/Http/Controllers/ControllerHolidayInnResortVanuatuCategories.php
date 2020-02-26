<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ModelsHolidayInnResortVanuatuCategories;

class ControllerHolidayInnResortVanuatuCategories extends Controller
{
        //
        public function HolidayInnResortVanuatuCategories() {
            return response()->json(ModelsHolidayInnResortVanuatuCategories::get(), 200);
        }
}
