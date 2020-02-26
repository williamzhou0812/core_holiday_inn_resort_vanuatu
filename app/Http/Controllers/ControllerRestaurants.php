<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ModelsRestaurant;


class ControllerRestaurants extends Controller
{
    //
    public function Restaurants() {
        return response()->json(ModelsRestaurant::get(), 200);
    }
}
