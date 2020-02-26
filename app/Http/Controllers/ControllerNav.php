<?php

namespace App\Http\Controllers;

use App\ModelsNav;
use Illuminate\Http\Request;

class ControllerNav extends Controller
{
    public function Navbar() {
        return response()->json(ModelsNav::get(), 200);
    }
}
