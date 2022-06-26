# Laravel MongoDB Cache Driver

This extension allows to store cache in MongoDB like memcache, redis, etc. in Laravel

## Installation

The preferred method of installing this library is with
[Composer](https://getcomposer.org/) by running the following from your project
root:

    $ composer require mageproger/mongocache
    
### Laravel without auto-discovery:

If you don't use auto-discovery, add the CacheServiceProvider to the providers array in config/app.php

```php
Mageproger\MongoCache\Providers\CacheServiceProvider::class,
```    
    

#### Copy the package config to your local config with the publish command:

```shell
php artisan vendor:publish --provider="Mageproger\MongoCache\Providers\CacheServiceProvider"
```

#### Add config to config/cache.php:

```shell
'mongo' => [
            'driver' => 'mongo',
            'host' => env('MONGO_HOST'),
            'database' => env('MONGO_DATABASE'),
            'username' => env('MONGO_USERNAME'),
            'password' => env('MONGO_PASSWORD'),
            'collection' => env('MONGO_COLLECTION'),
            'prefix' => env('MONGO_PREFIX', ''),
        ],
```


Use cache as usual
