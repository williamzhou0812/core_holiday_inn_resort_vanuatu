<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Showcase;

class ShowcasesController extends Controller
{
    public function showcases() {
        return response()->json(Showcase::where('display_status','1')->get(), 200);
    }
}
