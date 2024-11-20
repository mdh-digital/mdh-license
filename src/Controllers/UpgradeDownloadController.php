<?php

namespace MdhDigital\MdhLicense\Controllers;

use App\Models\Admin\License;
use App\Models\InternalSetting;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class UpgradeDownloadController extends Controller
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

        if (!$information->status) {
            return redirect()->route('upgrade.versions');
        }

        if (Storage::exists('uploads/upgrade/' . $information->code)) {
            return redirect()->route('upgrade.process.upgrade');
        }

        return view('vendor.upgrade.download', ['page'  => 'Download File Upgrade'], compact('settings', 'information'));
    }

    public function downloadFile()
    {

        if (!check_connection()) {
            return response()->json([
                'status'    => false,
                'message'   => 'Failed to Connect to WhatsMail.org server'
            ], 200); 
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
            return response()->json([
                'status'    => false,
                'message'   => 'Licensi tidak valid untuk melakukan pengunduhan'
            ], 200);
        }

        if (!$information->status) {
            return response()->json([
                'status'    => false,
                'message'   => 'Anda berada di versi terbaru'
            ], 200);
        }

        $retries = 9;
        $attempt = 0;
        $toDownload = null;
        $status = null;
        $files = null;

        while ($attempt < $retries && !$toDownload) {
            try {
                $toDownload = Http::withHeaders([
                    'Accept'     => 'application/json',
                    'businessId' => 'whatsmailorganisation',
                ])->timeout(120)
                    ->post(license_url() . '/api/versions/download', [
                        'license' => $license->purchase,
                        'email'   => $license->email,
                        'product' => $license->name,
                        'name'    => $information->url
                    ]);

                $status = $toDownload->status();
                $files  = $toDownload->body();
            } catch (\Exception $e) {
                $attempt++;
                if ($attempt >= $retries) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'Proses download gagal setelah beberapa kali percobaan'
                    ], 200);
                }
            }
        }

        if ($status == 200) {
            Storage::disk('local')->put("uploads/upgrade/{$information->code}.zip", $files);
            return response()->json([
                'status'    => true,
                'message'   => 'Proses unduh data berhasil'
            ], 200);
        } else {
            return response()->json([
                'status'    => false,
                'message'   => 'Terjadi kesalahan yang tidak diketahui'
            ], 200);
        }
    }
}
