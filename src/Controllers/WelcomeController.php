<?php

namespace MdhDigital\MdhLicense\Controllers;

use Illuminate\Routing\Controller;

class WelcomeController extends Controller
{
    /**
     * Display the installer welcome page.
     *
     * @return \Illuminate\Http\Response
     */
    public function welcome()
    {
        return view('vendor.installer.welcome');
    }

    public function offlineMode()
    {

        if (check_connection()) {
            return redirect()->back();
        }

        $connected = @fsockopen("www.google.com", 80);
        if ($connected) {
            $is_conn = true;
            fclose($connected);
        } else {
            $is_conn = false;
        }

        return view('vendor.upgrade.offline', compact('is_conn'));
    }
}
