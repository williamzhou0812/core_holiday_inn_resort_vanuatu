<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Header;

class HeadersController extends Controller
{
    public function headers() {
        return response()->json(Header::get(), 200);
    }
}
