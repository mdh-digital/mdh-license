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

        if ($callback->status == 200) {
            $newlicense = new License();
            $newlicense->name           = $request->username;
            $newlicense->purchase       = $request->purchase;
            $newlicense->email          = $request->email;
            $newlicense->ip_or_domain   = $request->domain;
            $newlicense->save();

            return response()->json([
                'pesan'     => 'Success Verification Purchase Code',
                'status'    => 'success'
            ]);
        } else {
            return response()->json([
                'pesan'     => $callback->response->description ?? '-',
                'status'    => 'error'
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

        if ($callback->status == 200) {
            $newlicense = License::first();
            $newlicense->name = $request->username;
            $newlicense->purchase = $request->purchase;
            $newlicense->email = $request->username;
            $newlicense->ip_or_domain = $domain;
            $newlicense->save();

            return response()->json([
                'pesan'     => 'Success Verification Purchase Code',
                'status'    => 'success'
            ]);
        } else {
            return response()->json([
                'pesan'     => $callback->response->description ?? '-',
                'status'    => 'error'
            ]);
        }
    }
}
