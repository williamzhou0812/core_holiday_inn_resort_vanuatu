<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ModelsAroundVanuatuSubCategoriesModel;

class ControllerAroundVanuatuSubCategories extends Controller
{
    public function AroundVanuatuSubCategories() {
        return response()->json(ModelsAroundVanuatuSubCategoriesModel::get(), 200);
    }
}
