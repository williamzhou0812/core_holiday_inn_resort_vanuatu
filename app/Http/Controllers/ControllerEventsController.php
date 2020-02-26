<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ModelsEventsModel;

class ControllerEventsController extends Controller
{
        public function Events() {
            return response()->json(ModelsEventsModel::get(), 200);
        }
    
}
