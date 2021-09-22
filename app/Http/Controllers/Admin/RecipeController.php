<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Recipe;
use App\Models\RecipeCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RecipeController extends Controller
{
    /**
     * @param Request $request
     * 菜谱列表
     */
    public function index(Request $request)
    {
        $name = $request->input('name');// 菜品名称
        $cateName = $request->input('cate_name');//分类名称
        $isRecommend = $request->input('is_recommend'); //推荐菜谱弹框展示 0 菜谱列表 1 推荐菜谱 -1 添加菜谱弹框
        $pageSize = $request->input('page_size','10');

        $recipeIdsArray = [];
        if($cateName){
            $catIds = DB::table('category')->where('name','like','%'.$cateName.'%')->pluck('id');
            $catIdsArray = $this->objectToArray($catIds);
            $recipeIds = DB::table('recipe_category')->whereIn('category_id',$catIdsArray)->pluck('recipe_id');
            $recipeIdsArray = $this->objectToArray($recipeIds);
            if(!$recipeIdsArray){
                return $this->successData([]);
            }
        }

        $query = DB::table('recipe')->select(['id','name','picture','is_local']);

        if($recipeIdsArray){
            $query->whereIn('id',$recipeIdsArray);
        }

        if($name){
            $query->where('name','like','%'.$name.'%');
        }

        if($isRecommend){
            $isRecommend = $isRecommend == -1 ?0:$isRecommend;
            $query->where('is_recommend',$isRecommend);
        }

        $data = $query->paginate($pageSize);
        $dataArray = $this->objectToArray($data);

        $recipeIds = array_column($dataArray['data'],'id');
        $cateIds = DB::table('recipe_category')->whereIn('recipe_id',$recipeIds)->select('category_id','recipe_id')->get();
        $cateArray = $this->objectToArray($cateIds);

        // 分类名称
        $cateData = [];
        $categoryName = $this->getCategoryName();
        foreach ($cateArray as $k=>$v){
            $cateData[$v['recipe_id']][] = $categoryName[$v['category_id']]??'';
        }

        foreach ($dataArray['data'] as $key=>$value){
            $dataArray['data'][$key]['cate_name'] = $cateData[$value['id']]??'';
        }

        return $this->successData($dataArray);
    }

    /**
     * 获取分类名称
     */
    private function getCategoryName()
    {
        $cate = DB::table('category')->select('id','name')->get();
        $cateArray = $this->objectToArray($cate);

        $cateName = array_column($cateArray,'name','id');
        return $cateName;
    }

    /**
     * @param Request $request
     * 添加食谱
     */
    public function addRecipe(Request $request)
    {
        $cateIds = $request->input('cate_ids');
        $name = $request->input('name');
        $picture = $request->input('picture');
        $isLocal = $request->input('is_local',0);

        if(!$name){
            return $this->errorMsg('菜品名称不可为空');
        }

        if(mb_strlen($name) > 10){
            return $this->errorMsg('菜品名称限制在十个字以内');
        }

        if(!$picture){
            return $this->errorMsg('菜品图片不可为空');
        }

        if(!$cateIds){
            return $this->errorMsg('所属分类不可为空');
        }

        $cateIds = explode(',',$cateIds);

        DB::beginTransaction();

        try {
            $recipe = new Recipe();
            $recipe->name = $name;
            $recipe->picture = $picture??'';
            $recipe->is_local = $isLocal??0;
            $recipe->save();
            $recipeId = $recipe->id;

            $CateData = [];
            foreach ($cateIds as $k=>$val){
                $CateData[] = [
                  'recipe_id'=>$recipeId,
                  'category_id'=>$val,
                  'addtime'=>date('Y-m-d H:i:s'),
                ];
            }

            DB::table('recipe_category')->insert($CateData);

            DB::commit();

        } catch (\Exception $e){
            DB::rollBack();

            return $this->errorMsg($e->getMessage());
        }
        return $this->successData();
    }

    /**
     * @param Request $request
     * 编辑食谱
     */
    public function editRecipe(Request $request)
    {
        $id = $request->input('id');
        $cateIds = $request->input('cate_ids');
        $name = $request->input('name');
        $picture = $request->input('picture');
        $isLocal = $request->input('is_local',0);

        if(!$id){
            return $this->errorMsg('菜品id不可为空');
        }

        if(!$name){
            return $this->errorMsg('菜品名称不可为空');
        }

        if(mb_strlen($name) > 10){
            return $this->errorMsg('菜品名称限制在十个字以内');
        }

        if(!$picture){
            return $this->errorMsg('菜品图片不可为空');
        }

        if(!$cateIds){
            return $this->errorMsg('所属分类不可为空');
        }

        $cateIds = explode(',',$cateIds);

        DB::beginTransaction();

        $recipe = Recipe::find($id);
        if(!$recipe){
            return $this->errorMsg('菜品不存在');
        }

        // 当前分类
        $recipeCate = DB::table('recipe_category')->where('recipe_id',$id)->pluck('category_id');
        $recipeCateArray = $this->objectToArray($recipeCate);

        // 分类前后对比
        $cateDel = array_diff($recipeCateArray,$cateIds);
        $cateAdd = array_diff($cateIds,$recipeCateArray);

        try {

            $recipe->name = $name;
            $recipe->picture = $picture??'';
            $recipe->is_local = $isLocal??0;
            $recipe->save();

            if($cateDel || $cateAdd){
                // 删除
                DB::table('recipe_category')->where('recipe_id',$id)->delete();

                // 添加
                $CateData = [];
                foreach ($cateIds as $k=>$val){
                    $CateData[] = [
                        'recipe_id'=>$id,
                        'category_id'=>$val,
                        'addtime'=>date('Y-m-d H:i:s'),
                    ];
                }
                DB::table('recipe_category')->insert($CateData);
            }

            DB::commit();

        } catch (\Exception $e){
            DB::rollBack();

            return $this->errorMsg($e->getMessage());
        }
        return $this->successData();
    }

    /**
     * @param Request $request
     * 删除菜品
     */
    public function deleteRecipe(Request $request)
    {
        $ids = $request->input('ids');
        $type = $request->input('type');// 0 菜谱列表 1 推荐菜谱
        if(!$ids){
            return $this->errorMsg('菜品id不可为空');
        }
        $idsArray = explode(',',$ids);
        if(!is_array($idsArray)){
            return $this->errorMsg('菜品id参数格式不正确');
        }

        if($type == 0){
            // 删除菜品以及菜品分类
            $res1 = Recipe::destroy($idsArray);
            $res2 = DB::table('recipe_category')
                ->whereIn('recipe_id',$idsArray)
                ->delete();

            if(!$res1 || !$res2){
                return $this->errorMsg('删除失败');
            }
        } else if ($type == 1) {
            $res = DB::table('recipe')
                ->whereIn('id',$idsArray)
                ->update(['is_recommend'=>0]);
            if(!$res){
                return $this->errorMsg('删除失败');
            }
        }

        return $this->successData();
    }

    /**
     * @param Request $request
     * 添加推荐菜谱
     */
    public function addRecommendRecipe(Request $request)
    {
        $id = $request->input('id');
        if(!$id){
            return $this->errorMsg('菜品id不可为空');
        }

        $recipe = Recipe::find($id);
        if(!$recipe){
            return $this->errorMsg('菜品不存在');
        }

        $recipe->is_recommend = 1;
        $res = $recipe->save();

        if(!$res){
            return $this->errorMsg();
        }
        return $this->successData();
    }

    /**
     * 最新菜谱
     */
    public function newestRecipe()
    {
        $data = DB::table('recipe')->select(['id','name','picture'])->limit(10)->orderBy('id')->get();
        $dataArray = $this->objectToArray($data);

        $recipeIds = array_column($dataArray,'id');
        // 分类
        $cateIds = DB::table('recipe_category')->whereIn('recipe_id',$recipeIds)->select('category_id','recipe_id')->get();
        $cateArray = $this->objectToArray($cateIds);

        // 制作流程
        $processIds = DB::table('recipe_process')->whereIn('recipe_id',$recipeIds)->select('recipe_id')->get();
        $processArray = $this->objectToArray($processIds);

        $isProcess = [];
        if($processArray){
            foreach ($processArray as $key=>$value){
                $isProcess[$value['recipe_id']] = $value['recipe_id'];
            }
        }

        // 分类名称
        $cateData = [];
        $categoryName = $this->getCategoryName();
        foreach ($cateArray as $k=>$v){
            $cateData[$v['recipe_id']][] = $categoryName[$v['category_id']]??'';
        }

        foreach ($dataArray as $key=>$value){
            $dataArray[$key]['is_process'] = $isProcess[$value['id']]??0;
            $dataArray[$key]['cate_name'] = $cateData[$value['id']]??'';
        }

        return $this->successData($dataArray);
    }

    /**
     * 添加菜品制作流程
     */
    public function addRecipeProcess(Request $request)
    {
        $type = $request->input('type');// 1 说明 2 控制 3 称重 4 食材明细

        switch ($type)
        {
            case 1:

                break;
        }
    }
}
