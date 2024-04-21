<?php

namespace MdhDigital\MdhLicense\Middleware;
 
use Closure;
use Illuminate\Http\Request;

class canNext
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $getSession = session()->get('storage_license');
        if($getSession != null) {
            return $next($request);
        }
       
        return redirect()->route('MdhLicense::license');
    }
}
