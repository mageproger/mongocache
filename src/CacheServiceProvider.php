<?php

namespace Mageproger\MongoCache;

use Mageproger\MongoCache\MongoStore;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

class CacheServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->booting(function () {
            Cache::extend('mongo', function ($app) {
                return Cache::repository(new MongoStore(array(
                    'host'       => $app['config']->get('cache.stores.mongo.host'),
                    'username'   => $app['config']->get('cache.stores.mongo.username'),
                    'password'   => $app['config']->get('cache.stores.mongo.password'),
                    'database'   => $app['config']->get('cache.stores.mongo.database'),
                    'collection' => $app['config']->get('cache.stores.mongo.collection'),
                    'prefix'     => $app['config']->get('cache.stores.mongo.prefix')
                )));
            });
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
