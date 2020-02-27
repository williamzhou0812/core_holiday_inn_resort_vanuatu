<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::get('posts', 'PostsPostsController@posts');
Route::get('pagemeta', 'ControllerPageMeta@PageMeta');
Route::get('navs', 'ControllerNav@Navbar');
Route::get('restaurantsandbars', 'ControllerRestaurants@Restaurants');
Route::get('gallery', 'ControllerGallery@Gallery');
Route::get('around_vanuatu', 'ControllerAroundVanuatuCategories@AroundVanuatuCategories');
Route::get('around_vanuatu_sublist', 'ControllerAroundVanuatuSubCategories@AroundVanuatuSubCategories');
Route::get('events', 'ControllerEventsController@Events');
Route::get('retailandservices', 'ControllerRetailAndService@RetailAndService');
Route::get('foodanddiningout', 'ControllerFoodAndDiningOut@FoodAndDiningOut');
Route::get('holidayinnresortvanauatucate', 'ControllerHolidayInnResortVanuatuCategories@HolidayInnResortVanuatuCategories');
Route::get('holidayinnresortvanauatusubpage', 'ControllerHolidayInnResortVanuatuSubPage@HolidayInnResortVanuatuSubPage');