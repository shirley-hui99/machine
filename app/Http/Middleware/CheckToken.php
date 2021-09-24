<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;

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
        // 检查此次请求中是否带有 token，如果没有则抛出异常
        $this->checkForToken($request);

        try{
            // 检测用户的登录状态，如果正常则通过
            if($this->auth->parseToken()->authenticate()){
                return $next($request);
            }

            throw new UnauthorizedHttpException('jwt-auth', '未登录');
        }catch (TokenExpiredException $exception){
            try{
                // 刷新用户token，并放到头部
                $token = $this->auth->refresh();
                // 使用下一次性登录，保证这次成功进入
                Auth::guard('admin')->onceUsingId($this->auth->manager()->getPayloadFactory()->buildClaimsCollection()->toPlainArray()['sub']);

            }catch(JWTException $exception){
                // 如果到这，就是代表refresh也过期了，需要重新登录了
                throw new UnauthorizedHttpException('jwt-auth', $exception->getMessage());
            }

            // 在响应头中返回新的token
            return $this->setAuthenticationHeader($next($request), $token);
        }
    }
}
