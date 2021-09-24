<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\App\wx\WXBizDataCrypt;
use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class LoginController extends Controller
{
    public function wxLogin(Request $request)
    {
        $code = $request->code;
        $nickname = $request->nickname;
        if (!$code || !$nickname) {
            return response()->json(['result' => 0, 'message' => "参数错误"]);
        }

        //授权
        $appid = "wx29206f235f2a6e2e";
        $secret = "da1b78eb5ef1c53fdf797b11d47dd492";
        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid=' . $appid . '&secret=' . $secret . '&js_code=' . $code . '&grant_type=authorization_code';

        $client = new Client();
        $response = $client->get($url);
        $res = json_decode($response->getBody(), true);
        if (isset($res['errcode'])) {
            return response()->json(['result' => 0, 'message' => $res['errmsg']]);
        }

        // 检查数据库是否有该用户openid
        $open_id = $res['openid'];
        $userid = DB::table('user')
            ->where('open_id', $open_id)
            ->value('id');

        if (!$userid) {
            $userid = DB::table('user')->insertGetId([
                'open_id' => $open_id,
                'username' => $nickname,
                'register_time' => date("Y-m-d H:i:s"),
                'login_time' => date("Y-m-d H:i:s"),
            ]);
        } else {
            DB::table('user')->where('id', $userid)->update(['login_time' => date("Y-m-d H:i:s"), 'session_key' => $res['session_key']]);
        }

        //加密openid把验证信息存放到redis中用来验证
        $keys_token = md5($open_id);
        Redis::set($keys_token, $userid);

        //设置30天为过期时间
        Redis::expire($keys_token, 60 * 60 * 24 * 15);
        return response()->json(['result' => 1, 'message' => "成功", 'userid' => $userid, 'token' => $keys_token]);
    }

    /**
     * 获取手机号
     *
     * @param Request $request
     * @return void
     */
    public function wxAuthorize(Request $request)
    {
        $token = $request->header('token');
        $uid = Redis::get($token);
        $appid = "wxbec60a7fe723f9b6";
        $session_key = DB::table('user')->where('id', $uid)->value('session_key');
        $encryptedData = $request->encryptedData;
        $iv = $request->iv;
        $pc = new wxBizDataCrypt($appid, $session_key);
        $errCode = $pc->decryptData($encryptedData, $iv, $data);
        if ($errCode == 0) {
            $data = json_decode($data);
            Db::table('user')->where('id', $uid)->update(['phone' => $data->phoneNumber]);
            return response()->json(['result' => 1, 'message' => "成功", 'phone' => $data->phoneNumber]);
        } else {
            return response()->json(['result' => 0, 'message' => "失败", 'data' => $errCode]);
        }
    }

}
