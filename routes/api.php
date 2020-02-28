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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
Route::get('posts', 'PostsController@posts');
Route::get('pagemeta', 'PageMetaController@PageMeta');
Route::get('navs', 'NavController@Navbar');
Route::get('restaurantsandbars', 'RestaurantController@Restaurants');
Route::get('gallery', 'GalleryController@Gallery');
Route::get('around_vanuatu', 'AroundVanuatuCategoriesController@AroundVanuatuCategories');
Route::get('around_vanuatu_sublist', 'AroundVanuatuSubCategoriesController@AroundVanuatuSubCategories');
Route::get('events', 'EventsController@Events');
Route::get('retailandservices', 'RetailAndServicesController@RetailAndService');
Route::get('foodanddiningout', 'FoodAndDiningOutController@FoodAndDiningOut');
Route::get('holidayinnresortvanauatucate', 'HolidayInnResortVanuatucategoriesController@HolidayInnResortVanuatuCategories');


