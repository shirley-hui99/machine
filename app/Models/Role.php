<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    //
    protected $table = 'role';

    public function RoleAccess()
    {
        return $this->hasMany('App\Models\RoleAccess','role_id','id');
    }
}
