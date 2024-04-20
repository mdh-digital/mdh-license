<?php

namespace MdhDigital\MdhLicense\Controllers;

use App\Models\Admin\License;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Request as FacadesRequest;
use Illuminate\Support\Facades\Validator;

class LicenseItemController extends Controller
{
    public function welcome()
    {
        return view('vendor.license.welcome');
    }

    public function validation()
    {

        return view('vendor.license.input');
    }

    public function checkValidation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'purchase'      => 'required',
            'username'      => 'required',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json([
                    'errors' => $validator->errors(),
                    'message' => 'error'
                ]);
            }
        }

        $deviceName     = getHostName();
        $domain         = substr(FacadesRequest::root(), 7); 

        $toServer =  Http::withHeaders([
            'businessId'    => 'pasarsafedepartment',
        ])->post('https://pasarsafe.com/api/codecanyon/purchase-code', [
            'code'          => $request->purchase,
            'username'      => $request->username,
            'domain'        => $domain,
            'device'        => $deviceName
        ]);

        $callback   = json_decode($toServer->body());

        if($callback->status == 200) {
            $newlicense = new License();
            $newlicense->name           = $request->username; 
            $newlicense->purchase       = $request->purchase;
            $newlicense->email          = $request->email; 
            $newlicense->ip_or_domain   = $request->domain; 
            $newlicense->save();

            return response()->json([
                'pesan'     => 'Berhasil memverifikasi kode',
                'status'    => 'success'
            ]);

        } else {
            return response()->json([
                'pesan'     => $callback->response->description ?? '-',
                'status'    => 'warning'
            ]);
        }

    }

    public function updateLicense()
    {
        $license = License::first();
        return view('vendor.license.update', ["page" => "Update License"], compact('license'));
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'purchase'      => 'required',
            'email'      => 'required|email',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json([
                    'errors' => $validator->errors(),
                    'message' => 'error'
                ]);
            }
        }

        $deviceName = getHostName();
        $domain = substr(FacadesRequest::root(), 7);
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://mdhpos.com/api/open/check-license',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array(
                'purchase' => $request->purchase,
                'email' => $request->email,
                'domain' => $domain,
                'device'    => $deviceName
            ),
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $hasil = json_decode($response);

        if ($hasil->status == 'error') {
            return response()->json([
                'pesan' => $hasil->message,
                'status' => $hasil->status
            ]);
        } else {
            $newlicense = License::first();
            $newlicense->name = '-';
            $newlicense->customer_id = $hasil->data->transaction_id;
            $newlicense->purchase = $hasil->data->purchase_code;
            $newlicense->email = $request->email;
            $newlicense->type = $hasil->data->use;
            $newlicense->ip_or_domain = $hasil->data->domain_or_ip;
            $newlicense->barcode_code = $hasil->data->barcode_encrypt;
            $newlicense->save();
            return response()->json([
                'pesan' => $hasil->message,
                'status' => $hasil->status
            ]);
        }
    }
}
