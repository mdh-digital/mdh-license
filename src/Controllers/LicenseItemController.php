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
            'email'         => 'required|email',
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

        if (!check_connection()) {
            $newlicense = new License();
            $newlicense->purchase       = $request->purchase;
            $newlicense->email          = $request->email;
            $newlicense->ip_or_domain   = $domain;
            $newlicense->name           = $request->product;
            $newlicense->save();

            return response()->json([
                'pesan'     => 'Success Verification Purchase Code',
                'status'    => 'success'
            ]);
        }

        $toServer =  Http::withHeaders([
            'businessId'    => 'whatsmailorganisation',
        ])->post('https://whatsmail.org/api/license/checking', [
            'purchase'      => $request->purchase,
            'email'         => $request->email,
            'product'       => $request->product,
            'domain'        => $domain,
            'device'        => $deviceName
        ]);

        $callback   = json_decode($toServer->body());

        if ($callback->status == 200) {
            License::create([
                'purchase'      => $request->purchase,
                'email'         => $request->email,
                'ip_or_domain'  => $domain,
                'name'          => $request->product,
                'version_name'  => env('VERSION_NAME'),
                'version_code'  => env('VERSION_CODE')
            ]);

            return response()->json([
                'pesan'     => 'Success Verification Purchase Code',
                'status'    => 'success'
            ]);
        } else {
            return response()->json([
                'pesan'     => $callback->message ?? '-',
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
            'email'         => 'required',
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

        if (!check_connection()) {

            License::first()->update([
                'purchase'      => $request->purchase,
                'email'         => $request->email,
                'ip_or_domain'  => $domain,
                'name'          => $request->product
            ]);

            return response()->json([
                'pesan'     => 'Success Verification Purchase Code',
                'status'    => 'success'
            ]);
        }


        $toServer =  Http::withHeaders([
            'businessId'    => 'whatsmailorganisation',
        ])->post('https://whatsmail.org/api/license/checking', [
            'purchase'      => $request->purchase,
            'email'         => $request->email,
            'product'       => $request->product,
            'domain'        => $domain,
            'device'        => $deviceName
        ]);

        $callback   = json_decode($toServer->body());

        if ($callback->status == 200) {

            License::first()->update([
                'purchase'      => $request->purchase,
                'email'         => $request->email,
                'ip_or_domain'  => $domain,
                'name'          => $request->product
            ]);


            return response()->json([
                'pesan'     => 'Success Verification Purchase Code',
                'status'    => 'success'
            ]);
        } else {
            return response()->json([
                'pesan'     => $callback->message ?? '-',
                'status'    => 'error'
            ]);
        }
    }
}
