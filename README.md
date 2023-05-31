## Notes

### Installation of [Google2FA for Laravel](https://github.com/antonioribeiro/google2fa-laravel) via CLI

```sh
$ composer require pragmarx/google2fa-laravel
$ php artisan vendor:publish --provider="PragmaRX\Google2FALaravel\ServiceProvider"
$ php artisan make:migration google_2fa
# Create the migration file content (see below)
$ php artisan migrate
# For the QR Code
$ composer require pragmarx/google2fa-qrcode
# QRCode Service to use
$ composer require chillerlan/php-qrcode
```

### Dependencies

You will need to install the imagick extension for PHP. More details here (also installed in the CLI):

* [https://github.com/chillerlan/php-qrcode](https://github.com/chillerlan/php-qrcode)

### Migrations

```php
<?php
// Implementation of google auth in User table
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google2fa_secret')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('google2fa_secret');
        });
    }
};
```

### 2FA Routes

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Google2FA;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Google 2FA landing page
Route::get('/google2fa/index', [Google2FA::class, 'index'])
    ->name('google2fa.index')
    ->middleware(['auth']);

// Authenticate Google 2FA
Route::post('/google2fa/authenticate', [Google2FA::class, 'authenticate'])
    ->name('google2fa.authenticate')
    ->middleware(['auth']);
```

### 2FA Controllers

```php
<?php

namespace App\Http\Controllers;

use PragmaRX\Google2FA\Google2FA as Google2FALib;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use chillerlan\QRCode\QRCode;
use PragmaRX\Google2FALaravel\Support\Constants;
use PragmaRX\Google2FALaravel\Support\Authenticator;

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
        $authenticator = app(Authenticator::class)->boot($request);

        if($auth_kind == 'register') {
            $secret_key = $request->session()->get('secret_key');
        } elseif ($auth_kind == 'authenticate') {
            $secret_key = Auth::user()->google2fa_secret;
        }

        $valid = $google_2fa->verifyKey($secret_key, $request->get('one_time_password'));

        if($valid) {
            $authenticator->login();

            if($auth_kind == 'register') {
                Auth::user()->google2fa_secret = $secret_key;
                Auth::user()->save();
            }

            return redirect('admin/main');
        } else {
            return redirect('google2fa/index?pass=no');
        }
    }
}
```

### 2FA Middleware

```php
<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Closure;
use PragmaRX\Google2FALaravel\Support\Authenticator;
use Symfony\Component\HttpFoundation\Response;

class TwoFA
{
    public function handle($request, Closure $next, string ...$guards): Response
    {
        $authenticator = app(Authenticator::class)->boot($request);

        if ($authenticator->isAuthenticated()) {
            return $next($request);
        }

        return redirect('google2fa/index');
    }
}
```

<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains over 2000 video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the Laravel [Patreon page](https://patreon.com/taylorotwell).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Cubet Techno Labs](https://cubettech.com)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[Many](https://www.many.co.uk)**
- **[Webdock, Fast VPS Hosting](https://www.webdock.io/en)**
- **[DevSquad](https://devsquad.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[OP.GG](https://op.gg)**
- **[WebReinvent](https://webreinvent.com/?utm_source=laravel&utm_medium=github&utm_campaign=patreon-sponsors)**
- **[Lendio](https://lendio.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
