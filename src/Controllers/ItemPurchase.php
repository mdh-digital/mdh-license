<?php

namespace MdhDigital\MdhLicense\Controllers;

use App\Models\Admin\License;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Request as FacadesRequest;

trait ItemPurchase
{

    public static function serverConnection()
    {
        $connected = @fsockopen("pasarsafe.com", 80);
        if ($connected) {
            $is_conn = true;
            fclose($connected);
        } else {
            $is_conn = false;
        }
        return $is_conn;
    }

    public static function getCredential()
    {
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
            return $callback->token;
        } else {
            return false;
        }
    }
}
