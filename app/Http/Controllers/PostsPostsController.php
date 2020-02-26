<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ModelsPostsModel;

class PostsPostsController extends Controller
{
    //
    public function posts() {
        return response()->json(ModelsPostsModel::get(), 200);
    }
}
