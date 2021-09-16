<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

//登录
Route::any('login','Admin\LoginController@index');
//设备
Route::post('device','Admin\DeviceController@index');
Route::post('addDevice','Admin\DeviceController@addDevice');
Route::post('editDevice','Admin\DeviceController@editDevice');
//分类
Route::post('addCategory','Admin\CategoryController@addCategory');