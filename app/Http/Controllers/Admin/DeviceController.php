<?php

namespace App\Http\Controllers\Admin;

use App\Models\Device as Devices;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeviceController extends Controller
{
    public function index(Request $request)
    {
        $barcode = $request->input('barcode');
        $area = $request->input('area');
        $pageSize = $request->input('page_size',10);

        $query = DB::table('device')->where(['is_deleted'=>0])
            ->select('id','barcode','factory_time','buy_time','area','ip_address','contacts','mobile','status','active_time','login_time');

        if($barcode){
            $query->where('barcode','like','%'.$barcode.'%');
        }

        // 省市区
        if(!$area){
            $query->where('area','like','%'.$area.'%');
        }

        $data = $query->paginate($pageSize);

        return $this->successData($data);

    }

    /**
     * 添加设备
     * @param Request $request
     */
    public function addDevice(Request $request)
    {
        $barcode = $request->input('barcode');
        $factoryTime = $request->input('factory_time');

        if(!$barcode || !$factoryTime){
            return $this->errorMsg('参数不全');
        }

        $device = new Devices();
        $device->barcode = $barcode;
        $device->factory_time = $factoryTime;

        $res = $device->save();

        if(!$res){
            return $this->errorMsg();
        }
        return $this->successData();
    }

    /**
     * @param Request $request
     * 编辑设备
     */
    public function editDevice(Request $request)
    {
        $id = $request->input('id');
        $contacts = $request->input('contacts');
        $buyTime = $request->input('buy_time');

        if(!$id || !$contacts || !$buyTime){
            return $this->errorMsg('参数不全');
        }

        $device = Devices::find($id);
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

    /**
     * @param Request $request
     * 删除设备
     */
    public function deleteDevice(Request $request)
    {
        $id = $request->input('id');

        if(!$id){
            return $this->errorMsg('id不可为空');
        }

        $device = Devices::find($id);
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

    /**
     * @param Request $request
     * 做菜记录
     */
    public function record(Request $request)
    {
        $barcode = $request->input('barcode');
        $name = $request->input('name');
        $mobile = $request->input('mobile');
        $pageSize = $request->input('page_size','10');

        $query = DB::table('cook_record');

        if($barcode){
            $query->where('barcode','like','%'.$barcode.'%');
        }

        if($name){
            $query->where('recipe_name','like','%'.$name.'%');
        }

        if($mobile){
            $query->where('mobile','like','%'.$barcode.'%');
        }

        $data = $query->paginate($pageSize);

        return $this->successData($data);
    }
}
