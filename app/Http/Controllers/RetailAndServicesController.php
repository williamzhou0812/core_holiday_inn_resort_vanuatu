<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\RetailAndService;

class RetailAndServicesController extends Controller
{
    //
    public function RetailAndService() {
        return response()->json(RetailAndService::get(), 200);
    }
}
