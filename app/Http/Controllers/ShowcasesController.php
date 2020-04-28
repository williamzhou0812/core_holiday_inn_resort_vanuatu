<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Showcase;

class ShowcasesController extends Controller
{
    public function showcases() {

        return response()->json(Showcase::
            where(function($q){
                // from and to are defined
                $now = new \DateTime('now');
                $q->where('display_status','1');
                $q->where('display_from', '<=', $now->format('Y-m-d H:i'));
                $q->where('display_to', '>=',  $now->format('Y-m-d H:i'));
            })
            ->orWhere(function($q) {
                // from is in the past and to not defined
                $now = new \DateTime('now');
                $q->where('display_status','1');
                $q->where('display_from', '<=', $now->format('Y-m-d H:i'));
                $q->whereNull('display_to');
            })
            ->orWhere(function($q) {
                // both from and to are NULL
                $q->where('display_status','1');
                $q->whereNull('display_from');
                $q->whereNull('display_to');
            })
            ->orWhere(function($q) {
                // both from and to are NULL
                $now = new \DateTime('now');
                $q->where('display_status','1');
                $q->whereNull('display_from');
                $q->where('display_to', '>=',  $now->format('Y-m-d H:i'));
            })
            ->get(), 200);
    }
}
