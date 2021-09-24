<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Album;
use App\Models\Recipe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class IndexController extends Controller
{
    /**
     * 推荐菜谱
     *
     * @param Request $request
     * @return void
     */
    public function recommendRecipe(Request $request)
    {
        $lists = Recipe::where('is_recommend', 1)->get();
        return response()->json(['result' => 1, 'message' => "成功", 'data' => $lists]);
    }

    /**
     * 最新菜谱
     *
     * @param Request $request
     * @return void
     */
    public function newRecipe(Request $request)
    {
        $qty = $request->qty;
        $qty = $qty ? $qty : 10;
        $lists = Recipe::orderby('created_at', 'desc')->limit($qty)->get();
        return response()->json(['result' => 1, 'message' => "成功", 'data' => $lists]);
    }

    /**
     * 我的专辑
     *
     * @param Request $request
     * @return void
     */
    public function getAlbum(Request $request)
    {
        $token = $request->header('token');
        $uid = Redis::get($token);
        $data = Album::with('recipe')->get()->toArray();
        var_dump($data);exit;
    }
}
