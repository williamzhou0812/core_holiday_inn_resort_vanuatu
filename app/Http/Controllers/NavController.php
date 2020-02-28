<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Nav;

class NavController extends Controller
{
    //
    public function Navbar() {
        return response()->json(Nav::get(), 200);
    }
}
