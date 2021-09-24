<?php

namespace App\Http\Middleware;
use Closure;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;

class ShouQuan
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
        $token = $request->header('token');
        if(!Redis::exists($token)){
            return response()->json(['result'=>2,'message'=>'授权过期,请重新授权!']);
        }
        return $next($request);
    }

}