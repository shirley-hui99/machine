<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function errorMsg($msg = '操作失败',$code = 4001){
        echo json_encode(['code'=>$code,'msg'=>$msg]);exit;
    }

    public function successData($data = [],$code = 200,$msg = '操作成功'){
        echo json_encode(['code'=>$code,'msg'=>$msg,'data'=>$data]);exit;
    }

    public function objectToArray($object){
        return json_decode(json_encode($object),true);
    }
}
