<?php

namespace MdhDigital\MdhLicense\Controllers;

use App\Models\Admin\License;
use App\Models\InternalSetting;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class UpgradeProcessController extends Controller
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

        if (Storage::exists('uploads/upgrade/' . $information->code)) {
            return redirect()->route('upgrade.process.upgrade');
        }

        $files = collect(Storage::disk('local')->listContents('/uploads/upgrade', false));
        $files = $files->filter(function ($file) use ($information) {
            return basename($file['path']) == $information->code . '.zip';
        });

        $files = $files->values()->all();

        if (count($files) == 0) {
            $contents = collect(Storage::disk('local')->listContents('/uploads/upgrade', false));

            if ($contents->count() > 0) {
                return redirect()->route('upgrade.versions');
            }

            return redirect()->route('upgrade.start');
        }

        return view('vendor.upgrade.process', ['page'  => 'Extrack File Upgrade'], compact('information', 'settings', 'files'));
    }

    public function upgrade()
    {

        if (!check_connection()) {
            return response()->json([
                'status'    => false,
                'message'   => 'Failed to Connect to WhatsMail.org server'
            ], 200); 
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

        $intructionFile     = 'uploads/upgrade/' . $information->code . '/instruction.php';

        if (!Storage::exists($intructionFile)) {

            if (Storage::exists('uploads/upgrade/' . $information->code)) {
                return redirect()->route('upgrade.versions');
            }

            return redirect()->route('upgrade.process.extrack');
        }

        $instruction        = include(Storage::path($intructionFile));
        return view('vendor.upgrade.update', ['page'  => 'Extrack File Upgrade'], compact('information', 'settings', 'instruction'));
    }

    public function extrackFile($fileName)
    {
        $toExtrack  = 'uploads/upgrade/' . pathinfo($fileName, PATHINFO_FILENAME);
        $filePath   = 'uploads/upgrade/' . $fileName;

        if (!Storage::exists($filePath)) {
            return response()->json(['status' => false, 'message' => 'ZIP file not found'], 200);
        }

        $absoluteZipPath        = Storage::path($filePath);
        $absoluteExtractPath    = Storage::path($toExtrack);

        $zip = new \ZipArchive;

        if ($zip->open($absoluteZipPath) === true) {
            if (!Storage::exists($toExtrack)) {
                Storage::makeDirectory($toExtrack);
            }

            $zip->extractTo($absoluteExtractPath);
            $zip->close();

            Storage::delete($filePath);

            return response()->json(['status' => true, 'message' => 'ZIP file extracted successfully']);
        } else {
            return response()->json(['status' => false, 'message' => 'Failed to open ZIP file'], 500);
        }
    }

    public function startProcess($version)
    {

        $license        = License::first(['id', 'version_code', 'version_name']);

        if ($version == $license->version_code) {
            return response()->json(['status' => false, 'message' => 'Versi ini sudah anda gunakan sebelumnya, silahkan refresh halaman']);
        }

        $intructionFile     = 'uploads/upgrade/' . $version . '/instruction.php';
        if (!Storage::exists($intructionFile)) {
            return response()->json(['status' => false, 'message' => 'Instruction file not found'], 200);
        }

        $instruction = include(Storage::path($intructionFile));

        $baseFolder = 'uploads/upgrade/' . $version;

        $basePath = base_path();

        $this->moveFiles($instruction, $baseFolder, $basePath);
        $this->runMigration($instruction);
        $this->runSeeder($instruction);

        $license->update([
            'version_code'      => $instruction['upgrade_information']['code'],
            'version_name'      => $instruction['upgrade_information']['name']
        ]);

        Storage::delete("$baseFolder/instruction.php");
        Storage::delete("$baseFolder/image.jpeg");
        Storage::deleteDirectory('uploads/upgrade/' . $version);


        return response()->json(['status' => true, 'message' => 'Upgrade process completed successfully']);
    }

    public function moveFiles($instruction, $baseFolder, $basePath)
    {

        if (!empty($instruction['files']) && $instruction['files'] === true) {
            $files = Storage::allFiles($baseFolder);

            foreach ($files as $file) {
                $fileName = basename($file);

                if (in_array($fileName, ['instruction.php', 'image.jpeg'])) {
                    continue;
                }

                $relativePath = str_replace("$baseFolder/", '', $file);
                // Tentukan lokasi tujuan di base Laravel
                $destinationPath = base_path($relativePath);

                // Buat folder tujuan jika belum ada
                if (!file_exists(dirname($destinationPath))) {
                    mkdir(dirname($destinationPath), 0755, true);
                }

                // Pindahkan file menggunakan File facade
                File::move(public_path($file), $destinationPath);
            }

            $folders = Storage::allDirectories($baseFolder);

            foreach ($folders as $folder) {
                // Jika folder kosong setelah file dipindahkan, hapus foldernya
                if (empty(Storage::allFiles($folder))) {
                    Storage::deleteDirectory($folder);
                }
            }

            // Hapus folder utama jika kosong
            if (empty(Storage::allFiles($baseFolder)) && empty(Storage::allDirectories($baseFolder))) {
                Storage::deleteDirectory($baseFolder);
            }
        }
    }

    public function runMigration($instruction)
    {
        if (!empty($instruction['migration']['status']) && $instruction['migration']['status'] === true) {
            $commands = $instruction['migration']['commands'] ?? [];
            if (!empty($commands)) {
                foreach ($commands as $command) {
                    Artisan::call('migrate', ['--path' => $command]);
                }
            } else {
                Artisan::call('migrate');
            }
        }
    }

    public function runSeeder($instruction)
    {
        if (!empty($instruction['seeder']['status']) && $instruction['seeder']['status'] === true) {
            $commands = $instruction['seeder']['commands'] ?? [];
            if (!empty($commands)) {
                foreach ($commands as $command) {
                    Artisan::call('db:seed', ['--class' => $command]);
                }
            } else {
                Artisan::call('db:seed');
            }
        }
    }
}
