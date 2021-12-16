<?php

namespace App\Providers;


use Illuminate\Support\ServiceProvider;
use App\Foundation\Handlers\AesEncryptHandler;
use App\Foundation\Modules\Pocket\PocketHandle;
use App\Foundation\Modules\Context\ContextHandler;
use App\Foundation\Modules\Repository\RepositoriesHandle;
use App\Foundation\Modules\Response\ApiBusinessResponseHandle;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        //        Validator::extend('file_exists', 'App\Rules\FileExistsValidator@passes');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        \Carbon\Carbon::setLocale('zh');

        $this->app->singleton('context', function () {
            return ContextHandler::instance();
        });

        $this->app->singleton('rep', function () {
            return RepositoriesHandle::instance();
        });

        $this->app->singleton('api_rr', function () {
            return new ApiBusinessResponseHandle();
        });

//        if ($this->app->environment() !== 'production') {
            $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
            $this->app->register(\BeyondCode\DumpServer\DumpServerServiceProvider::class);
//        }

        $this->app->singleton('pocket', function () {
            return PocketHandle::instance();
        });

        $this->app->singleton('aes_encrypt', function () {
            return new AesEncryptHandler();
        });
    }
}
