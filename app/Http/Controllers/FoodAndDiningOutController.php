<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\FoodAndDiningOut;

class FoodAndDiningOutController extends Controller
{
    //
    public function FoodAndDiningOut() {
        return response()->json(FoodAndDiningOut::get(), 200);
    }
}
