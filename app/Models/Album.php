<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Album extends Model
{
    protected $table = 'album';

    /**
     * 专辑拥有多个菜谱
     *
     * @return void
     */
    public function recipe()
    {
        return $this->belongsToMany('App\Models\Recipe');
    }
}
