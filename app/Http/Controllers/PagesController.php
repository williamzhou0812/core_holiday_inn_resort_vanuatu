<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Section;
use Illuminate\Support\Facades\DB;

class PagesController extends Controller
{
    public function pages($tableReference) {
        // find the model of the section
        $section = Section::where('table_reference', $tableReference)->first();
        // make sure section is defined for requested table
        if (!isset($section)) {
            return response()->json(array('error'=>'Model not found.'), 404);
        }

        // get from database
        //DB::table($tableReference)->where('id', $item->id)->update(['position' => $item->position]);
        $pages = DB::table($tableReference)->where('display_status', '1')->orderBy('position','asc')->get();
        return response()->json($pages, 200);
    }

    public function page($tableReference, $id) {
        // find the model of the section
        $section = Section::where('table_reference', $tableReference)->first();
        // make sure section is defined for requested table
        if (!isset($section)) {
            return response()->json(array('error'=>'Model not found.'), 404);
        }

        // get from database
        $page = DB::table($tableReference)->where('id', $id)->get();
        return response()->json($page, 200);
    }
}
