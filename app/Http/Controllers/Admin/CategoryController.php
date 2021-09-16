<?php

namespace App\Http\Controllers\Admin;

use App\Device;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $data = DB::table('category')->select();
        return $this->successData($data);

    }

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

        $insert['name'] = $name;
        $insert['pid'] = $pid??0;
        $insert['picture'] = $picture??'';
        $insert['addtime'] = date('Y-m-d H:i:s');

        $res = DB::table('category')->insert($insert);

        if(!$res){
            return $this->errorMsg();
        }

        return $this->successData();
    }

    public function editDevice(Request $request)
    {
        $id = $request->input('id');
        $contacts = $request->input('contacts');
        $buyTime = $request->input('buy_time');

        if(!$id || !$contacts || !$buyTime){
            return $this->errorMsg('参数不全');
        }

        $device = Device::find($id);

        if(!$device){
            return $this->errorMsg('设备不存在');
        }

        $device->contacts = $contacts;
        $device->buy_time = $buyTime;

        $res = $device->save();

        if(!$res){
            return $this->errorMsg();
        }

        return $this->successData();
    }

    public function deleteDevice(Request $request)
    {
        $id = $request->input('id');

        if(!$id){
            return $this->errorMsg('id不可为空');
        }

        $device = Device::find($id);

        if(!$device){
            return $this->errorMsg('设备不存在');
        }

        $device->is_deleted = 1;
        $res = $device->save();

        if(!$res){
            return $this->errorMsg();
        }

        return $this->successData();
    }
}
