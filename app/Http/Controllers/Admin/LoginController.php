<?php

namespace App\Http\Controllers\Admin;

use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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

        $admin = DB::table('admin')->where(['mobile'=>$mobile,'status'=>0])->first();

        if(!Hash::check( $password , $admin->password)){
            return $this->errorMsg('密码错误，请重新输入');
        }

        $credentials = request(['mobile', 'password']);
        if (! $token = auth('admin')->attempt($credentials)) {
            return $this->errorMsg('验证失败');
        }

        // update token
        DB::table('admin')->where('id', $admin->id)->update(['token'=>$token]);

        $data = [
            'token'=>$token
        ];

        return $this->successData($data);

    }
}
