<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Event;

class EventsController extends Controller
{
    //
    public function Events() {
        return response()->json(Event::get(), 200);
    }
}
