<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ModelsFoodAndDiningOut;


class ControllerFoodAndDiningOut extends Controller
{
    public function FoodAndDiningOut() {
        return response()->json(ModelsFoodAndDiningOut::get(), 200);
    }
}
