<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ModelsHolidayInnResortVanuatuSubPage;

class ControllerHolidayInnResortVanuatuSubPage extends Controller
{
    public function HolidayInnResortVanuatuSubPage() {
        return response()->json(ModelsHolidayInnResortVanuatuSubPage::get(), 200);
    }
}
