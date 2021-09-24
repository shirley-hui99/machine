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
Route::post('login', 'Admin\LoginController@index');
//设备
Route::post('device', 'Admin\DeviceController@index');
Route::post('addDevice', 'Admin\DeviceController@addDevice');
Route::post('editDevice', 'Admin\DeviceController@editDevice');
//分类
Route::post('category', 'Admin\CategoryController@index');
Route::post('addCategory', 'Admin\CategoryController@addCategory');
Route::post('editCategory', 'Admin\CategoryController@editCategory');
Route::post('deleteCategory', 'Admin\CategoryController@deleteCategory');
Route::post('uploadImage', 'Admin\CategoryController@uploadImage');
// 菜谱
Route::post('recipe', 'Admin\RecipeController@index');
Route::post('addRecipe', 'Admin\RecipeController@addRecipe');
Route::post('editRecipe', 'Admin\RecipeController@editRecipe');
Route::post('deleteRecipe', 'Admin\RecipeController@deleteRecipe');
Route::post('addRecommendRecipe', 'Admin\RecipeController@addRecommendRecipe');
Route::post('newestRecipe', 'Admin\RecipeController@newestRecipe');
Route::post('addRecipeProcess', 'Admin\RecipeController@addRecipeProcess');
Route::post('editRecipeProcess', 'Admin\RecipeController@editRecipeProcess');
Route::post('deleteRecipeProcess', 'Admin\RecipeController@deleteRecipeProcess');
Route::post('recipeProcess', 'Admin\RecipeController@recipeProcess');
Route::post('recipeProcessDetail', 'Admin\RecipeController@recipeProcessDetail');
//用户
Route::post('user', 'Admin\UserController@index');
Route::post('addMember', 'Admin\UserController@addMember');
Route::post('addMemberDate', 'Admin\UserController@addMemberDate');
Route::post('member', 'Admin\UserController@member');
// 账号
Route::post('account', 'Admin\AccountController@index');
Route::post('addAccount', 'Admin\AccountController@addAccount');
Route::post('editAccount', 'Admin\AccountController@editAccount');
Route::post('deleteAccount', 'Admin\AccountController@deleteAccount');
Route::post('resetPwd', 'Admin\AccountController@resetPwd');
Route::post('changePwd', 'Admin\AccountController@changePwd');
// 角色
Route::post('role', 'Admin\RoleController@index');
