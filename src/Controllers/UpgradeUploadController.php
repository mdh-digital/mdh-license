<?php

namespace MdhDigital\MdhLicense\Controllers;

use App\Models\Admin\License;
use App\Models\InternalSetting;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UpgradeUploadController extends Controller
{

    /**
     * Display the updater welcome page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {

        if (!check_connection()) {
            return redirect()->route('upgrade.versions')->with(['gagal' => 'Failed to Connect to WhatsMail.org server']);
        }

        $contents = collect(Storage::disk('local')->listContents('/uploads/upgrade', false));

        if ($contents->count() > 0) {
            return redirect()->route('upgrade.process.extrack');
        }

        $license        = License::first(['purchase', 'email', 'name', 'version_code']);
        $response       = Http::withHeaders([
            'Accept'        => 'application/json',
            'businessId'    => 'whatsmailorganisation',
        ])->post(license_url() . '/api/versions/to-upgrade', [
            'license'           => $license->purchase,
            'email'             => $license->email,
            'product'           => $license->name,
            'version'           => $license->version_code
        ]);

        $information = json_decode($response->body());

        if ($response->status() != 200) {
            return redirect()->route('upgrade.versions');
        }

        if (!$information->status) {
            return redirect()->route('upgrade.versions');
        }

        if (Storage::exists('uploads/upgrade/' . $information->code)) {
            return redirect()->route('upgrade.process.upgrade');
        }

        $settings   = InternalSetting::first(['logo', 'app_name']);
        return view('vendor.upgrade.upload', ['page'  => 'Upload File Upgrade'], compact('settings', 'information'));
    }

    public function uploadFile(Request $request)
    {

        if (!check_connection()) {
            return redirect()->route('upgrade.versions')->with(['gagal' => 'Failed to Connect to WhatsMail.org server']);
        }
        
        $validator = Validator::make($request->all(), [
            'file'      => 'required|file|mimes:zip',
            'version'   => 'required'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }


        if ($request->hasFile('file')) {
            $file = $request->file('file');

            // Buat nama unik untuk file
            $fileName = $request->version . '.zip';
            $file->storeAs('uploads/upgrade', $fileName, 'local');

            return redirect()->route('upgrade.process.extrack');
        }

        return redirect()->back()->with(['gagal'    => 'Proses upload gagal, silahkan coba kembali']);
    }
}
