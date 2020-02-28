<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Restaurant;

class RestaurantController extends Controller
{
    //
    public function Restaurants() {
        return response()->json(Restaurant::get(), 200);
    }
}
