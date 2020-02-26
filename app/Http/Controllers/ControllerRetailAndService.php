<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ModelsRetailAndService;

class ControllerRetailAndService extends Controller
{
    public function RetailAndService() {
        return response()->json(ModelsRetailAndService::get(), 200);
    }
}
