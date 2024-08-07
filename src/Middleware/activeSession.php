<?php

namespace MdhDigital\MdhLicense\Middleware;

use Closure;

use App\Models\Admin\License;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Request as FacadesRequest;

class activeSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Illuminate\Http\RedirectResponse|mixed
     */
    public function handle($request, Closure $next)
    {

        $connected  = @fsockopen("mdh-digital.com", 80);
        $is_conn    = false;
        if ($connected) {
            $is_conn = true; 
            fclose($connected);
        }  

        if(!$is_conn) {
            return $next($request);
        }
        
        if (session()->get('active_session') != null) {
            return $next($request);
        } else {
            $getLicense         = License::first();
            $deviceName         = getHostName();
            $domain             = substr(FacadesRequest::root(), 7);

            $response       = Http::withHeaders([
                'Accept'        => 'application/json',
                'businessId'    => 'pasarsafeproduct',
            ])->post('https://product.mdh-digital.com/api/license/get-credential', [
                'purchase'          => $getLicense->purchase,
                'email'             => $getLicense->email,
                'product'           => $getLicense->name,
                'domain'            => $domain,
                'device'            => $deviceName
            ]);

            $callback = json_decode($response->body());

            if ($callback->status == 200) {
                session()->put('active_session', $callback->token);
                return $next($request);
            } else {
                return redirect()->route('license.update');
            }
        }
    }
}
