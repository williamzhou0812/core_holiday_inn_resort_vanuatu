<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Section;

class SectionsController extends Controller
{
    public function sections() {
        return response()->json(Section::get(), 200);
    }
}
