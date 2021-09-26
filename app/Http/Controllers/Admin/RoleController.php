<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    /**
     * @param Request $request
     * 角色管理
     */
    public function index(Request $request)
    {
        $name = $request->input('name');
        $pageSize = $request->input('page_size',10);

        $query = Role::with(['RoleAccess'=>function ($query){
            $query->select('access_id','role_id');
        }]);

        if($name){
            $query->where('name','like','%'.$name.'%');
        }

        $data = $query->paginate($pageSize);

        return $this->successData($data);
    }

    /**
     * @param Request $request
     * 添加角色
     */
    public function addRole(Request $request)
    {
        $name = $request->input('name');
        $accessIds = $request->input('access_ids');

        if(mb_strlen($name) > 10 || mb_strlen($name) < 3){
            return $this->errorMsg('角色名称限制3-10位字数');
        }

        if(!$accessIds){
            return $this->errorMsg('权限id不可为空');
        }

        $accessIdsArray = explode(',',$accessIds);

        DB::beginTransaction();
        try {
            $role = new Role();
            $role->name = $name;
            $role->save();
            $roleId = $role->id;

            $accessData = [];
            foreach ($accessIdsArray as $k=>$val){
                $accessData[] = [
                    'role_id'=>$roleId,
                    'access_id'=>$val,
                    'add_time'=>date('Y-m-d H:i:s'),
                ];
            }

            DB::table('role_access')->insert($accessData);

            DB::commit();

        } catch (\Exception $e){
            DB::rollBack();

            return $this->errorMsg($e->getMessage());
        }
        return $this->successData();
    }

    /**
     * @param Request $request
     * 编辑角色
     */
    public function editRole(Request $request)
    {
        $id = $request->input('id');
        $name = $request->input('name');
        $accessIds = $request->input('access_ids');

        if(!$id){
            return $this->errorMsg('角色id不可为空');
        }

        $role = Role::find($id);
        if(!$role){
            return $this->errorMsg('角色不存在');
        }

        if(mb_strlen($name) > 10 || mb_strlen($name) < 3){
            return $this->errorMsg('角色名称限制3-10位字数');
        }

        if(!$accessIds){
            return $this->errorMsg('权限id不可为空');
        }

        $accessIdsArray = explode(',',$accessIds);

        // 当前角色权限
        $roleAccess = DB::table('role_access')->where('role_id',$id)->pluck('access_id');
        $roleAccessArray = $this->objectToArray($roleAccess);

        // 前后对比
        $accessDel = array_diff($roleAccessArray,$accessIdsArray);
        $accessAdd = array_diff($accessIdsArray,$roleAccessArray);

        DB::beginTransaction();
        try {
            $role->name = $name;
            $role->save();

            if($accessAdd){
                $accessData = [];
                foreach ($accessAdd as $k=>$val){
                    $accessData[] = [
                        'role_id'=>$id,
                        'access_id'=>$val,
                        'add_time'=>date('Y-m-d H:i:s'),
                    ];
                }
                DB::table('role_access')->insert($accessData);
            }

            // 删除
            if($accessDel){
                DB::table('role_access')->where('role_id',$id)->whereIn('access_id',$accessDel)->delete();
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
     * 删除角色
     */
    public function deleteRole(Request $request)
    {
        $ids = $request->input('ids');
        if(!$ids){
            return $this->errorMsg('角色id不可为空');
        }
        $idsArray = explode(',',$ids);
        if(!is_array($idsArray)){
            return $this->errorMsg('角色id参数格式不正确');
        }

        $exist = DB::table('admin')->whereIn('role_id',$idsArray)->first();
        if($exist){
            return $this->errorMsg('角色下存在账号，不可删除');
        }

        // 删除角色以及角色权限
        $res1 = Role::destroy($idsArray);
        $res2 = DB::table('role_access')->whereIn('role_id',$idsArray)->delete();

        if(!$res1 || !$res2){
            return $this->errorMsg('删除失败');
        }

        return $this->successData();
    }

    /**
     * 权限列表
     */
    public function access()
    {
        $data = DB::table('access')->select('id','name')->get();
        return $this->successData($data);
    }
}
