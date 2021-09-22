<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recipe extends Model
{
    protected $table = 'recipe';

    public function RecipeCategory()
    {
        return $this->hasMany('App\Models\RecipeCategory');
    }
}
