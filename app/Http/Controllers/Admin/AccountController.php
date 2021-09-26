<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

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
        $pageSize = $request->input('page_size',10);

        $query = Admin::with(['Role'],function($query) use ($roleName){
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
     * @param Request $request
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

    /**
     * 当前APP版本
     */
    public function version()
    {
        $version = DB::table('version')->orderByDesc('id')->value('version');
        return $this->successData($version);
    }

    /**
     * @param Request $request
     * @return $this|\Illuminate\Http\RedirectResponse
     * 上传文件
     */
    public function uploadFile(Request $request)
    {
        $file = $request->file('file');
        if(!isset($file)){
            return $this->errorMsg('请上传文件！');
        }

        if(!$file->isValid()){
            return $this->errorMsg('请上传有效的文件！');
        }

        $ext = $file->getClientOriginalExtension();
        $path = $file->getRealPath();
        //文件后缀
        $imageTypes = array('zip','rar','exe');
        if(!in_array($ext,$imageTypes)){
            return $this->errorMsg('仅支持zip、rar、exe格式！');
        }

        //保存图片
        $save_name = uniqid()  .'.'. $ext;
        $bool = Storage::disk('files')->put($save_name,file_get_contents($path));
        if(!$bool){
            return $this->errorMsg('文件上传失败！');
        }

        //保存路径
        $img_web_path = 'uploads/files/' .$save_name;

        return $this->successData($img_web_path);
    }

    /**
     * @param Request $request
     * 更新APP版本
     */
    public function updateVersion(Request $request)
    {
        $version = $request->input('version');
        $fileUrl = $request->input('file_url');

        if(!$version){
            return $this->errorMsg('版本号不可为空！');
        }

        if(!$fileUrl){
            return $this->errorMsg('文件不可为空！');
        }

        $currentVersion = DB::table('version')->orderByDesc('id')->value('version');

        if(!version_compare($version,$currentVersion,'gt')){
            return $this->errorMsg('新版本号小于当前版本！');
        }

        $saveData = [
            'version'=>$version,
            'url'=>$fileUrl,
            'add_time'=>date('Y-m-d H:i:s'),
        ];

        $res = DB::table('version')->insert($saveData);
        if(!$res){
            return $this->errorMsg();
        }
        return $this->successData();
    }
}
