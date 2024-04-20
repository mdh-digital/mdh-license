<?php

namespace Mdhdigital\MdhLicense\Controllers;

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
            'Accept' => 'application/json', 
        ])->post('https://pasarsafe.com/api/open/get-credential', [
                'purchase'      => $getLicense->purchase,
                'email'         => $getLicense->email,
                'domain'        => $domain,
                'device'        => $deviceName
        ]);

        $hasil = json_decode($response->body());

        if ($hasil->status == 'error') {
            return false;
        }

        if ($hasil->status == 'success') {
            return $hasil->token;
        }
    }
}
