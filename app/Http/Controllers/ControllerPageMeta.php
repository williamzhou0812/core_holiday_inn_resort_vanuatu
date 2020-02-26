<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ModelsPageMeta;

class ControllerPageMeta extends Controller
{
    //
    public function PageMeta() {
        return response()->json(ModelsPageMeta::get(), 200);
    }
}
