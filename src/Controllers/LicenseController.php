<?php

namespace MdhDigital\MdhLicense\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Request as FacadesRequest;

class LicenseController extends Controller
{
    /**
     * Display the installer welcome page.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('vendor.installer.license');
    }

    public function savingCredencial(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'purchase'      => 'required|string',
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

        if (!check_connection()) {
            session()->put('storage_license', $request->purchase);
            session()->put('storage_username', $request->email);
            session()->put('product_type', $request->product);
            session()->put('version_code', env('VERSION_CODE'));
            session()->put('version_name', env('VERSION_NAME'));

            return redirect()->route('MdhLicense::requirements');
        }

        $deviceName     = getHostName();
        $domain         = substr(FacadesRequest::root(), 7);

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

            session()->put('storage_license', $request->purchase);
            session()->put('storage_username', $request->email);
            session()->put('product_type', $request->product);

            return redirect()->route('MdhLicense::requirements');
        } else {
            return redirect()->back()->with(['failed' => $callback->message]);
        }
    }
}
