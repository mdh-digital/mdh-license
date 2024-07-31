<?php

namespace MdhDigital\MdhLicense\Controllers;

use App\Models\Admin\License;
use Illuminate\Routing\Controller;
use MdhDigital\MdhLicense\Events\MdhLicenseFinished;
use MdhDigital\MdhLicense\Helpers\EnvironmentManager;
use MdhDigital\MdhLicense\Helpers\FinalInstallManager;
use MdhDigital\MdhLicense\Helpers\InstalledFileManager;
use Illuminate\Support\Facades\Request as FacadesRequest;

class FinalController extends Controller
{
    /**
     * Update installed file and display finished view.
     *
     * @param \MdhDigital\MdhLicense\Helpers\InstalledFileManager $fileManager
     * @param \MdhDigital\MdhLicense\Helpers\FinalInstallManager $finalInstall
     * @param \MdhDigital\MdhLicense\Helpers\EnvironmentManager $environment
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function finish(InstalledFileManager $fileManager, FinalInstallManager $finalInstall, EnvironmentManager $environment)
    {
        $finalMessages = $finalInstall->runFinal();

        $deviceName     = getHostName();
        $domain         = substr(FacadesRequest::root(), 7);       

        $finalStatusMessage = $fileManager->update(); 
        $finalEnvFile       = $environment->getEnvContent();

        event(new MdhLicenseFinished);

        $license =  License::create([
            'name'          => env('PRODUCT_TYPE'),
            'email'         => env('PURCHASE_USERNAME'),
            'purchase'      => env('PURCHASE_CODE'),
            'ip_or_domain'  => $domain,
            'type'          => 'online'
        ]);

        session()->put('license_activation', $license->purchase);
        session()->put('username_activation', $license->name);

        return view('vendor.installer.finished', compact('finalMessages', 'finalStatusMessage', 'finalEnvFile'));
    }
}
