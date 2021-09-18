<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $data = DB::table('category')->select();
        return $this->successData($data);
    }

    /**
     * @param Request $request
     * 添加分类
     */
    public function addCategory(Request $request)
    {
        $pid = $request->input('pid');
        $name = $request->input('name');
        $picture = $request->input('picture');

        if(!$name){
            return $this->errorMsg('分类名称不可为空');
        }

        if(strlen($name) > 10){
            return $this->errorMsg('分类名称限制在十个字以内');
        }

        if($pid != 0 && !$picture){
            return $this->errorMsg('图片不可为空');
        }

        $category = new Category();
        $category->name = $name;
        $category->pid = $pid??0;
        $category->picture = $picture??'';
        $res = $category->save();

        if(!$res){
            return $this->errorMsg();
        }
        return $this->successData();
    }

    /**
     * @param Request $request
     * 编辑分类
     */
    public function editCategory(Request $request)
    {
        $id = $request->input('id');
        $name = $request->input('name');
        $picture = $request->input('picture');

        if(!$name){
            return $this->errorMsg('分类名称不可为空');
        }

        if(mb_strlen($name) > 10){
            return $this->errorMsg('分类名称限制在十个字以内');
        }

        $category = Category::find($id);
        if(!$category){
            return $this->errorMsg('菜品分类不存在');
        }

        if($category->pid != 0 && !$picture){
            return $this->errorMsg('图片不可为空');
        }

        $category->name = $name;
        $category->picture = $picture??'';
        $res = $category->save();

        if(!$res){
            return $this->errorMsg();
        }
        return $this->successData();
    }

    /**
     * @param Request $request
     * 删除分类
     */
    public function deleteCategory(Request $request)
    {
        $id = $request->input('id');
        if(!$id){
            return $this->errorMsg('分类id不可为空');
        }

        $category = Category::find($id);
        if(!$category){
            return $this->errorMsg('分类不存在');
        }

        $secondCateArray = [];
        if($category->pid == 0){
            $secondCateIds = DB::table('category')->where('pid',$id)->pluck('id');
            $secondCateArray = $this->objectToArray($secondCateIds);
            // 当前分类下是否有菜品
            if($secondCateIds){
                $exist = DB::table('recipe_category')->whereIn('category_id',$secondCateArray)->first();
                if($exist){
                    return $this->errorMsg('该分类下存在菜品，不可删除');
                }
            }
        }

        // 删除一级和二级
        array_push($secondCateArray,$id);
        $res = Category::destroy($secondCateArray);

        if(!$res){
            return $this->errorMsg();
        }
        return $this->successData();
    }
}
