<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Section;
use App\DataType;
use App\DataRow;
use Illuminate\Support\Facades\DB;
use Mockery\CountValidator\Exception;

class PagesController extends Controller
{
    public function pages($tableReference) {
        // find the model of the section
        $section = Section::where('table_reference', $tableReference)->first();
        // make sure section is defined for requested table
        if (!isset($section)) {
            return response()->json(array('error'=>'Model not found.'), 404);
        }
        // get data type id for this table
        $dataType = DataType::where('name', $tableReference)->select('id')->first();
        $dataTypeId = $dataType->id;
        // get data rows
        $dataRows = DataRow::where('data_type_id', $dataTypeId)->get();
        // find out if display_from and display_to exists
        $hasDisplayFrom = false;
        $hasDisplayTo = false;
        foreach($dataRows as $row) {
            if (strtolower($row->field) == 'display_from' && $row->edit == 1) {
                $hasDisplayFrom = true;
            }
            else if (strtolower($row->field) == 'display_to' && $row->edit == 1)
                $hasDisplayTo = true;
        }

        // get from database depends with display from/to , else do not filter
        $pages = array();
        if ($hasDisplayFrom && $hasDisplayTo) {
            $pages = DB::table($tableReference)
                ->where(function($q){
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
                ->orderBy('position','asc')->get();
        }
        else {
            $pages = DB::table($tableReference)->where('display_status', '1')->orderBy('position','asc')->get();
        }
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
