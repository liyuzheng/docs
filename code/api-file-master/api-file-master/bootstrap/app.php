<?php

require_once __DIR__ . '/../vendor/autoload.php';

(new Laravel\Lumen\Bootstrap\LoadEnvironmentVariables(
    dirname(__DIR__)
))->bootstrap();

date_default_timezone_set(env('APP_TIMEZONE', 'PRC'));

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| Here we will load the environment and create the application instance
| that serves as the central piece of this framework. We'll use this
| application as an "IoC" container and router for this framework.
|
*/

$app = new Laravel\Lumen\Application(
    dirname(__DIR__)
);

$app->withFacades();
$app->register(Jenssegers\Mongodb\MongodbServiceProvider::class);
$app->register(\Illuminate\Mail\MailServiceProvider::class);
$app->withEloquent();

/*
|--------------------------------------------------------------------------
| Register Container Bindings
|--------------------------------------------------------------------------
|
| Now we will register a few bindings in the service container. We will
| register the exception handler and the console kernel. You may add
| your own bindings here if you like or you can make another file.
|
*/

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

/*
|--------------------------------------------------------------------------
| Register Config Files
|--------------------------------------------------------------------------
|
| Now we will register the "app" configuration file. If the file exists in
| your configuration directory it will be loaded; otherwise, we'll load
| the default version. You may register other files below as needed.
|
*/

$app->configure('app');
$app->configure('custom');
$app->configure('database');
$app->configure('queue');
$app->configure('sms');
$app->configure('redis_keys');
$app->configure('filesystems');
$app->configure('netease');
$app->configure('logging');
$app->configure('sentry');
$app->configure('fengkong');
$app->configure('wechat');
$app->configure('mail');
$app->configure('routes_map');

/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
|
| Next, we will register the middleware with the application. These can
| be global middleware that run before and after each request into a
| route or middleware that'll be assigned to some specific routes.
|
*/

$app->middleware([
    App\Http\Middleware\CorsMiddleware::class,
]);

$app->routeMiddleware([
    'auth'                   => App\Http\Middleware\AuthenticateMiddleware::class,
    'optional_auth'          => App\Http\Middleware\OptionalAuthenticateMiddleware::class,
    'base'                   => App\Http\Middleware\BaseMiddleware::class,
    'admin'                  => App\Http\Middleware\AdminMiddleware::class,
    'is_audit'               => App\Http\Middleware\Adapter\IsAuditVersionAdapter::class,
    'blocked_verify'         => App\Http\Middleware\BlockVerifyMiddleware::class,
    'xss'                    => App\Http\Middleware\XssMiddleware::class,
    'android_income_decimal' => App\Http\Middleware\Adapter\AndroidIncomeDecimalAdapter::class,
    'callback'               => App\Http\Middleware\CallBackMiddleware::class
]);

/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
|
| Here we will register all of the application's service providers which
| are used to bind services into the container. Service providers are
| totally optional, so you are not required to uncomment this line.
|
*/

$app->register(App\Providers\AppServiceProvider::class);
$app->register(App\Providers\AuthServiceProvider::class);
$app->register(App\Providers\EventServiceProvider::class);
$app->register(Illuminate\Redis\RedisServiceProvider::class);
$app->register(App\Foundation\Modules\FormRequest\FormRequestServiceProvider::class);
$app->register(Sentry\Laravel\ServiceProvider::class);
$app->register(Intervention\Image\ImageServiceProvider::class);

$app->alias('mail.manager', Illuminate\Mail\MailManager::class);
$app->alias('mail.manager', Illuminate\Contracts\Mail\Factory::class);
/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
|
| Next we will include the routes file so that they can all be added to
| the application. This will provide all of the URLs the application
| can respond to, as well as the controllers that may handle them.
|
*/

$app->router->group([
    'namespace' => 'App\Http\Controllers',
], function ($router) use ($app) {
    require __DIR__ . '/../routes/v1.php';
    require __DIR__ . '/../routes/v2.php';
    require __DIR__ . '/../routes/v99.php';
    require __DIR__ . '/../routes/callback.php';
    require __DIR__ . '/../routes/internal.php';
    if ($app->environment() != 'production') {
        require __DIR__ . '/../routes/developer.php';
    }
});

return $app;
