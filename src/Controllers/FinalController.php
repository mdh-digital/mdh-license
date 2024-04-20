<?php

namespace MdhDigital\MdhLicense\Controllers;

use Illuminate\Routing\Controller;
use MdhDigital\MdhLicense\Events\LaravelInstallerFinished;
use MdhDigital\MdhLicense\Helpers\EnvironmentManager;
use MdhDigital\MdhLicense\Helpers\FinalInstallManager;
use MdhDigital\MdhLicense\Helpers\InstalledFileManager;

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
        $finalStatusMessage = $fileManager->update();
        $finalEnvFile = $environment->getEnvContent();

        event(new LaravelInstallerFinished);

        return view('vendor.installer.finished', compact('finalMessages', 'finalStatusMessage', 'finalEnvFile'));
    }
}
