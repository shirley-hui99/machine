<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Recipe;
use App\Models\RecipeCategory;
use App\Models\RecipeProcess;
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
        $pageSize = $request->input('page_size',10);

        $catIdsArray = [];
        if($cateName){
            $catIds = DB::table('category')->where('name','like','%'.$cateName.'%')->pluck('id');
            $catIdsArray = $this->objectToArray($catIds);
            if(!$catIdsArray){
                return $this->successData([]);
            }
        }

        $query = Recipe::whereHas('RecipeCategory',function($query) use ($catIdsArray){
            if($catIdsArray){
                $query->whereIn('category_id',$catIdsArray);
            }

        })->with(['RecipeCategory:category_id,recipe_id'],function($query) use ($catIdsArray){
            if($catIdsArray){
                $query->whereIn('category_id',$catIdsArray);
            }
        })->select(['id','name','picture','is_local']);

        if($name){
            $query->where('name','like','%'.$name.'%');
        }

        if($isRecommend){
            $isRecommend = $isRecommend == -1 ?0:$isRecommend;
            $query->where('is_recommend',$isRecommend);
        }

        $data = $query->paginate($pageSize);
        $dataArray = $this->objectToArray($data);

        //分类名称
        $categoryName = $this->getCategoryName();

        foreach ($dataArray['data'] as $key=>$value){
            if($value['recipe_category']){
                foreach ($value['recipe_category'] as $k=>$val){
                    $dataArray['data'][$key]['recipe_category'][$k]['cate_name'] = $categoryName[$val['category_id']]??'';
                }
            }
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
        $type = $request->input('type',0);// 0 菜谱列表 1 推荐菜谱
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
        $data = Recipe::with('RecipeCategory:category_id,recipe_id')
            ->select(['id','name','picture'])
            ->limit(10)
            ->orderBy('id')
            ->get()
            ->toArray();

        $recipeIds = array_column($data,'id');
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
        $categoryName = $this->getCategoryName();

        foreach ($data as $key=>$value){
            $data[$key]['is_process'] = $isProcess[$value['id']]??0;
            if($value['recipe_category']){
                foreach ($value['recipe_category'] as $k=>$val){
                    $data[$key]['recipe_category'][$k]['cate_name'] = $categoryName[$val['category_id']]??'';
                }
            }
        }

        return $this->successData($data);
    }

    /**
     * @param Request $request
     * 添加菜品制作流程
     */
    public function addRecipeProcess(Request $request)
    {
        $recipeId = $request->input('recipe_id');// 菜品id
        $type = $request->input('type',1);// 1 说明 2 控制 3 称重 4 食材明细
        $content = $request->input('content');// json数组
        if(!$recipeId){
            return $this->errorMsg('菜品id不可为空');
        }

        if(!$content){
            return $this->errorMsg('制作流程内容不可为空');
        }

        $recipe = Recipe::find($recipeId);
        if(!$recipe){
            return $this->errorMsg('菜品不存在');
        }

        $data = DB::table('recipe_process')->where(['recipe_id'=>$recipeId,'type'=>$type])->value('id');
        if($data){
            return $this->errorMsg('当前制作流程已存在');
        }

        $recipeProcess = new RecipeProcess();
        $recipeProcess->recipe_id = $recipeId;
        $recipeProcess->type = $type;
        $recipeProcess->content = $content;

        $res = $recipeProcess->save();

        if(!$res){
            return $this->errorMsg();
        }
        return $this->successData();
    }

    /**
     * @param Request $request
     * 编辑菜品制作流程
     */
    public function editRecipeProcess(Request $request)
    {
        $processId = $request->input('process_id');// 制作流程id
        $content = $request->input('content');// json数组
        if(!$processId){
            return $this->errorMsg('菜品制作流程id不可为空');
        }

        if(!$content){
            return $this->errorMsg('制作流程内容不可为空');
        }

        $recipeProcess = RecipeProcess::find($processId);
        if(!$recipeProcess){
            return $this->errorMsg('制作流程不存在');
        }

        $recipeProcess->content = $content;
        $res = $recipeProcess->save();

        if(!$res){
            return $this->errorMsg();
        }
        return $this->successData();
    }

    /**
     * @param Request $request
     * 删除菜品制作流程
     */
    public function deleteRecipeProcess(Request $request)
    {
        $processId = $request->input('process_id');// 制作流程id
        if(!$processId){
            return $this->errorMsg('菜品制作流程id不可为空');
        }

        $recipeProcess = RecipeProcess::find($processId);
        if(!$recipeProcess){
            return $this->errorMsg('制作流程不存在');
        }

        $res = $recipeProcess->delete();
        if(!$res){
            return $this->errorMsg();
        }
        return $this->successData();
    }

    /**
     * @param Request $request
     * 菜品其中一个制作流程
     */
    public function recipeProcessDetail(Request $request)
    {
        $processId = $request->input('process_id');// 制作流程id
        if(!$processId){
            return $this->errorMsg('菜品制作流程id不可为空');
        }

        $recipeProcess = RecipeProcess::find($processId);
        if(!$recipeProcess){
            return $this->errorMsg('制作流程不存在');
        }

        $recipeProcess->content = json_decode($recipeProcess->content,true)??[];

        return $this->successData($recipeProcess);
    }

    /**
     * @param Request $request
     * 菜品制作流程
     */
    public function recipeProcess(Request $request)
    {
        $recipeId = $request->input('recipe_id');// 菜品id
        if(!$recipeId){
            return $this->errorMsg('菜品id不可为空');
        }

        $recipeProcess = DB::table('recipe_process')
            ->where('recipe_id',$recipeId)
            ->select('id','type','content')
            ->get()
            ->toArray();
        if(!$recipeProcess){
            return $this->errorMsg('制作流程不存在');
        }

        foreach ($recipeProcess as $key=>&$value){
            $value->content = json_decode($value->content,true)??[];
        }

        return $this->successData($recipeProcess);
    }
}
