<?php

namespace App\Http\Controllers\Admin;

use App\Models\Admin;
use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function index(Request $request)
    {
        $mobile = $request->input('mobile');
        $password = $request->input('password');

        if(!$mobile){
            return $this->errorMsg('手机号不可为空');
        }

        if(!$password){
            return $this->errorMsg('密码不可为空');
        }

        $admin = Admin::with('Role')->where(['mobile'=>$mobile,'status'=>0])->first();

        if(!$admin){
            return $this->errorMsg('账号不存在，请重新输入');
        }

        if(!$admin->role){
            return $this->errorMsg('账号角色不存在');
        }

        if(!Hash::check( $password , $admin->password)){
            return $this->errorMsg('密码错误，请重新输入');
        }

        $credentials = request(['mobile', 'password']);
        if (! $token = auth('admin')->attempt($credentials)) {
            return $this->errorMsg('验证失败');
        }
//        if (! $token = auth('api')->login($admin)) {
//            return $this->errorMsg('验证失败');
//        }

        // update token
        DB::table('admin')->where('id', $admin->id)->update(['token'=>$token]);

        // 当前角色权限
        $roleAccess = DB::table('role_access')->where('role_id',$admin->role_id)->pluck('access_id');

        $data = [
            'id'=>$admin->id,
            'token'=>$token,
            'mobile'=>$mobile,
            'role_id'=>$admin->role_id,
            'role_name'=>$admin->role->name,
            'role_access'=>$roleAccess
        ];

        return $this->successData($data);

    }

    public function logout()
    {
        auth('admin')->logout(true);
    }
}
