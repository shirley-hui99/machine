<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
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

//小程序授权登录
Route::post('wxLogin', 'App\LoginController@wxLogin');

//首页
Route::post('recommendRecipe', 'App\IndexController@recommendRecipe'); //获取推荐菜谱
Route::post('newRecipe', 'App\IndexController@newRecipe'); //获取推荐菜谱

// Route::middleware(['ShouQuan'])->group(function () {
//
// });
