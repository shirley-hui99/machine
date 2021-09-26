<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class IndexController extends Controller
{
    public function index()
    {
        $onlineDevice = DB::table('device')->where('status',1)->count();
        $cook = DB::table('cook_record')->count();
        $device = DB::table('device')->count();

        $data = [
          'onlineDevice'=>  $onlineDevice,
          'cook'=>  $cook,
          'device'=>  $device,
        ];

        return $this->successData($data);
    }
}
