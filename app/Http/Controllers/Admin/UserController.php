<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    /**
     * @param Request $request
     * 用户管理
     */
    public function index(Request $request)
    {
        $username = $request->input('username');
        $mobile = $request->input('mobile');
        $pageSize = $request->input('page_size','10');

        $query = DB::table('user')
                ->select('id','username','mobile','login_time','register_time');

        if($username){
            $query->where('username','like','%'.$username.'%');
        }

        if($mobile){
            $query->where('mobile','like','%'.$mobile.'%');
        }

        $data = $query->paginate($pageSize);

        return $this->successData($data);

    }

    /**
     * @param Request $request
     * 会员管理
     */
    public function member(Request $request)
    {
        $mobile = $request->input('mobile');
        $pageSize = $request->input('page_size','10');

        $query = DB::table('user')
            ->where('is_member',1)
            ->select('id','mobile','expire_time','add_time');


        if($mobile){
            $query->where('mobile','like','%'.$mobile.'%');
        }

        $data = $query->paginate($pageSize);

        return $this->successData($data);

    }

    /**
     * @param Request $request
     * 增加会员
     */
    public function addMember(Request $request)
    {
        $mobile = $request->input('mobile');

        $user = User::where(['mobile'=>$mobile])->first();
        if(isset($user->is_member) && $user->is_member == 1){
            return $this->errorMsg('当前手机号已经是会员');
        }

        $expireTime = date("Y-m-d", strtotime('+6 months', time()));
        $data = [
            'is_member'=>1,
            'add_time'=>date('Y-m-d H:i:s'),
            'expire_time'=>$expireTime,
        ];

        if($user){
            $res = User::where('mobile',$mobile)->update($data);
        } else {
            $data['mobile'] = $mobile;
            $res = User::create($data);
        }

        if(!$res){
            return $this->errorMsg();
        }
        return $this->successData();
    }

    /**
     * @param Request $request
     * 增加权益
     */
    public function addMemberDate(Request $request)
    {
        $id = $request->input('id');
        $expireTime = $request->input('expire_time');

        $user = User::find($id);
        if(isset($user->is_member) && $user->is_member != 1){
            return $this->errorMsg('当前手机号非会员，不可增加权益');
        }

        $res = User::where('id',$id)->update(['expire_time'=>$expireTime]);
        if(!$res){
            return $this->errorMsg();
        }
        return $this->successData();
    }
}
