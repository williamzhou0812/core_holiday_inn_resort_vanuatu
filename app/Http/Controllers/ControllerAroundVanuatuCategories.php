<?php

namespace App\Http\Controllers;

use App\ModelsAroundVanuatuCategoriesModel;
use Illuminate\Http\Request;

class ControllerAroundVanuatuCategories extends Controller
{
    public function AroundVanuatuCategories() {
        return response()->json(ModelsAroundVanuatuCategoriesModel::get(), 200);
    }
}
