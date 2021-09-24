<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('username', 100)->comment('昵称');
            $table->string('mobile', 20)->comment('手机号');
            $table->dateTime('login_time')->comment('最近登录时间');
            $table->dateTime('create_at')->comment('注册时间');
            $table->string('open_id', 255)->comment('用户唯一标识');
            $table->dateTime('expire_time')->comment('到期时间');
            $table->dateTime('add_time')->comment('购买日期(新增会员的时间)');
            $table->dateTime('update_at')->comment('修改时间');
            $table->tinyInteger('status', 1)->comment('状态 0 正常 1 已删除');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
