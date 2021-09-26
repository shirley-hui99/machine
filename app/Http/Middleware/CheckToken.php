<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Redis;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;
use Tymon\JWTAuth\Facades\JWTAuth;

class CheckToken extends BaseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $newToken = null;
        $auth = JWTAuth::parseToken();
        if (! $token = $auth->setRequest($request)->getToken()) {
            return response()->json([
                'code' => '2',
                'msg' => '无参数token',
            ]);
        }

        try {
            $user = $auth->authenticate($token);
            if (! $user) {
                return response()->json([
                    'code' => '2',
                    'msg' => '未查询到该用户信息',
                ]);
            }
            $request->headers->set('Authorization','Bearer '.$token);
        } catch (TokenExpiredException $e) {
            try {
                sleep(rand(1,5)/100);
                $newToken = JWTAuth::refresh($token);
                // 给当前的请求设置性的token,以备在本次请求中需要调用用户信息
                $request->headers->set('Authorization','Bearer '.$newToken);
                // 将旧token存储在redis中,15天再次请求是有效的
                Redis::setex('token_blacklist:'.$token,60 * 60 * 24 * 15,$newToken);
            } catch (JWTException $e) {
                // 在黑名单的有效期,放行
                if($newToken = Redis::get('token_blacklist:'.$token)){
                    $request->headers->set('Authorization','Bearer '.$newToken); // 给当前的请求设置性的token,以备在本次请求中需要调用用户信息
                    return $next($request);
                }
                // 过期用户
                return response()->json([
                    'code' => '2',
                    'msg' => '账号信息过期了，请重新登录',
                ]);
            }
        } catch (JWTException $e) {
            return response()->json([
                'code' => '2',
                'msg' => '无效token',
            ]);
        }
        $response = $next($request);

        if ($newToken) {
            $response->headers->set('Authorization', 'Bearer '.$newToken);
        }

        return $response;

//        return response()->json(compact('user'));
    }
}
