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
          
            session()->put('storage_license',$request->purchase);
            session()->put('storage_username',$request->username);

            return redirect()->route('MdhLicense::requirements'); 

        } else {
            return redirect()->back()->with(['failed' => 'Sorry, your purchase code is invalid']);
            
        }
    }
}
