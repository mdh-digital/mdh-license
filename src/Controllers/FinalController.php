<?php

namespace Mdhdigital\MdhLicense\Controllers;

use Illuminate\Routing\Controller;
use Mdhdigital\MdhLicense\Events\LaravelInstallerFinished;
use Mdhdigital\MdhLicense\Helpers\EnvironmentManager;
use Mdhdigital\MdhLicense\Helpers\FinalInstallManager;
use Mdhdigital\MdhLicense\Helpers\InstalledFileManager;

class FinalController extends Controller
{
    /**
     * Update installed file and display finished view.
     *
     * @param \Mdhdigital\MdhLicense\Helpers\InstalledFileManager $fileManager
     * @param \Mdhdigital\MdhLicense\Helpers\FinalInstallManager $finalInstall
     * @param \Mdhdigital\MdhLicense\Helpers\EnvironmentManager $environment
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function finish(InstalledFileManager $fileManager, FinalInstallManager $finalInstall, EnvironmentManager $environment)
    {
        $finalMessages = $finalInstall->runFinal();
        $finalStatusMessage = $fileManager->update();
        $finalEnvFile = $environment->getEnvContent();

        event(new LaravelInstallerFinished);

        return view('vendor.installer.finished', compact('finalMessages', 'finalStatusMessage', 'finalEnvFile'));
    }
}
