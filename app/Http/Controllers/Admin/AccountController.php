<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AccountController extends Controller
{
    /**
     * @param Request $request
     * 账号管理
     */
    public function index(Request $request)
    {
        $mobile = $request->input('mobile');
        $roleName = $request->input('role_name');
        $pageSize = $request->input('page_size','10');

        $query = Admin::whereHas('Role',function($query) use ($roleName){
            $query->where('name','like','%'.$roleName.'%');
        })->where('status',0)->select('id','mobile','role_id');

        if($mobile){
            $query->where('mobile','like','%'.$mobile.'%');
        }

        $data = $query->paginate($pageSize);

        return $this->successData($data);
    }

    /**
     * 添加账号
     * @param Request $request
     */
    public function addAccount(Request $request)
    {
        $roleId = $request->input('role_id');
        $mobile = $request->input('mobile');

        if(!$mobile){
            return $this->errorMsg('手机号不可为空');
        }

        if(!$roleId){
            return $this->errorMsg('角色不可为空');
        }

        $admin = Admin::where(['mobile'=>$mobile,'status'=>0])->first();
        if($admin){
            return $this->errorMsg('当前手机号已存在');
        }

        $data = [
            'mobile'=>$mobile,
            'add_time'=>date('Y-m-d H:i:s'),
            'role_id'=>$roleId,
            'password'=>Hash::make('88888888'),
            'token'=>''
        ];

        $res = Admin::create($data);

        if(!$res){
            return $this->errorMsg();
        }
        return $this->successData();
    }

    /**
     * 编辑账号
     * @param Request $request
     */
    public function editAccount(Request $request)
    {
        $roleId = $request->input('role_id');
        $adminId = $request->input('admin_id');

        if(!$adminId){
            return $this->errorMsg('账号id不可为空');
        }

        if(!$roleId){
            return $this->errorMsg('角色不可为空');
        }

        $admin = Admin::find($adminId);
        if(!$admin){
            return $this->errorMsg('当前账户不存在');
        }

        $res = Admin::where('id',$adminId)->update(['role_id'=>$roleId]);

        if(!$res){
            return $this->errorMsg();
        }
        return $this->successData();
    }

    /**
     * @param Request $request
     * 删除账号
     */
    public function deleteAccount(Request $request)
    {
        $adminIds = $request->input('admin_ids');
        if(!$adminIds){
            return $this->errorMsg('账户id不可为空');
        }

        $adminIdsArray = explode(',',$adminIds);

        $res = DB::table('admin')
            ->whereIn('id',$adminIdsArray)
            ->update(['status'=>1]);

        if(!$res){
            return $this->errorMsg();
        }
        return $this->successData();
    }

    /**
     * 重置密码
     * @param Request $request
     */
    public function resetPwd(Request $request)
    {
        $adminId = $request->input('admin_id');

        if(!$adminId){
            return $this->errorMsg('账号id不可为空');
        }

        $admin = Admin::find($adminId);
        if(!$admin){
            return $this->errorMsg('当前账户不存在');
        }

        $pwd = Hash::make('88888888');
        $res = Admin::where('id',$adminId)->update(['password'=>$pwd]);

        if(!$res){
            return $this->errorMsg();
        }
        return $this->successData();
    }

    /**
     * 修改密码
     */
    public function changePwd(Request $request)
    {
        $adminId = $request->input('admin_id');
        $oldPwd = $request->input('old_pwd');
        $newPwd = $request->input('new_pwd');

        if(!$adminId){
            return $this->errorMsg('账号id不可为空');
        }

        $admin = Admin::find($adminId);
        if(!$admin){
            return $this->errorMsg('当前账户不存在');
        }

        if(!Hash::check( $oldPwd , $admin->password)){
            return $this->errorMsg('旧密码错误，请重新输入');
        }

        $pwd = Hash::make($newPwd);
        $res = Admin::where('id',$adminId)->update(['password'=>$pwd]);

        if(!$res){
            return $this->errorMsg();
        }
        return $this->successData();
    }
}
