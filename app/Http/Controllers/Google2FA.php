<?php

namespace App\Http\Controllers;

use PragmaRX\Google2FA\Google2FA as Google2FALib;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use chillerlan\QRCode\QRCode;

class Google2FA extends Controller
{
    /**
     * Page for 2FA requests
     */
    public function index(Request $request): View {
        // Kind of auth
        $auth_kind = 'register';
        $secret_key = '';
        $qrcode_url = '';

        if(trim(Auth::user()->google2fa_secret) != '') {
            $auth_kind = 'authenticate';
        } else {
            // Generate QR Code
            $google_2fa = new Google2FALib();

            // Store the secret key in session
            $secret_key = $google_2fa->generateSecretKey();
            $request->session()->put('secret_key', $secret_key);

            // Generate the QR from Google
            $google2_fa_inline = (new \PragmaRX\Google2FAQRCode\Google2FA());
            $qrcode_url = $google2_fa_inline->getQRCodeUrl(
                'Organization',
                Auth::user()->email,
                $secret_key
            );

            $qrcode_url = (new QRCode)->render($qrcode_url);
        }

        return view(
            'google2fa.index',
            ['auth_kind' => $auth_kind, 'secret_key' => $secret_key, 'qrcode_url' => $qrcode_url]
        );
    }

    /**
     * Removes the 2FA for the user
     */
    public function remove_2fa(Request $request) {
        $secret_key = Auth::user()->google2fa_secret = '';
        Auth::user()->save();

        return redirect()->back();
    }

    /**
     * Authentication for 2FA
     */
    public function authenticate(Request $request) {
        $auth_kind = $request->post('auth_kind');
        $one_time_password = $request->post('one_time_password');
        $secret_key = null;
        $google_2fa = new Google2FALib();

        if($auth_kind == 'register') {
            $secret_key = $request->session()->get('secret_key');
        } elseif ($auth_kind == 'authenticate') {
            $secret_key = Auth::user()->google2fa_secret;
        }

        $valid = $google_2fa->verifyKey($secret_key, $request->get('one_time_password'));

        if($valid) {
            if($auth_kind == 'register') {
                Auth::user()->google2fa_secret = $secret_key;
                Auth::user()->save();
            }

            return redirect('admin/main');
        }
    }
}
