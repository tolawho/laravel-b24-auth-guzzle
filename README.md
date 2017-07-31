# laravel-bitrix24-guzzle
This is middleware for lavarel that ensures the user has bitrix24 authorization token.

# Features
Two step auth
Check expires
Refresh User Data on 2 step of auth
Check auth


# Installation
Step 1.
In .env:
B24_HOSTNAME=https://[yourhostname].bitrix24.ru
B24_CLIENT_ID=
B24_CLIENT_SECRET=

Step 2.
In app/Http/Kernel.php:

    protected $routeMiddleware = [
        'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        //ADD THIS ->
        'b24auth'=>\zedsh\laravel\B24\Auth::class,
    ];


Step 3.
In routes/web.php:
//Add middleware with param 'two' in root of site - two step of auth work over this.
Route::get('/', 'HomeController@index')->name('root page')->middleware('b24auth:two');
//Add middleware with param 'init' in both route of site, and step 1 auth work on this route. After auth work redirect to root and work step two.
Route::get('/b24_state', 'HomeController@getB24State')->name('b24_state')->middleware('b24auth:init');


# For refresh user 
In app/User.php:
//Add Method in User class:

    public function B24Refresh()
    {
    Auth::user()->update(['b_user_id'=>$this->B24Creds()->user_id]);
    }

