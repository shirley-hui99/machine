<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RoleController extends Controller
{
    /**
     * @param Request $request
     * 角色管理
     */
    public function index(Request $request)
    {
        $name = $request->input('name');
        $pageSize = $request->input('page_size','10');

        $query = Role::with(['RoleAccess'=>function ($query){
            $query->select('access_id','role_id');
        }]);

        if($name){
            $query->where('name','like','%'.$name.'%');
        }

        $data = $query->paginate($pageSize);

        return $this->successData($data);
    }
}
