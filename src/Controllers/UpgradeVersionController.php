<?php

namespace MdhDigital\MdhLicense\Controllers;

use App\Models\Admin\License;
use App\Models\InternalSetting;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class UpgradeVersionController extends Controller
{

    /**
     * Display the updater welcome page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {

        $license        = License::first(); 
        $response       = Http::withHeaders([
            'Accept'        => 'application/json',
            'businessId'    => 'whatsmailorganisation',
        ])->post(license_url() . '/api/versions/latest', [
            'license'           => $license->purchase,
            'email'             => $license->email,
            'product'           => $license->name,
            'version'           => $license->version_code
        ]);


        $response = json_decode($response->body());

        return view('vendor.upgrade.index', ['page'  => 'Upgrade Version', 'breadcumb' => false], compact('response', 'license'));
    }

    public function start()
    {

        if (!check_connection()) {
            return redirect()->route('upgrade.versions')->with(['gagal' => 'Failed to Connect to WhatsMail.org server']);
        }

        $contents = collect(Storage::disk('local')->listContents('/uploads/upgrade', false));

        if ($contents->count() > 0) {
            return redirect()->route('upgrade.process.extrack');
        }

        $settings       = InternalSetting::first(['logo', 'app_name']);
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

        if (Storage::exists('uploads/upgrade/' . $information->code)) {
            return redirect()->route('upgrade.process.upgrade');
        }

        return view('vendor.upgrade.start', ['page'  => 'Upgrade Version'], compact('settings', 'information'));
    }
}
