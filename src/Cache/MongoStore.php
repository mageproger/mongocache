<?php

namespace Mageproger\MongoCache;

use Illuminate\Contracts\Cache\Store;
use MongoDB\Client as Mongo;

class MongoStore implements Store
{
    /**
     * The mongo instance
     *
     * @param  \Mongo
     */
    protected $connection;

    /**
     * The mongo connection instance
     *
     * @param  \MongoConnection
     */
    protected $collection;

    /**
     * The Mongo config array
     *
     * @var array
     */
    protected $config;

    /**
     * A string that should be prepended to keys.
     *
     * @var string
     */
    protected $prefix;

    /**
     * Create a new Mongo cache store.
     *
     * @param  array $config
     *     - $config['host']       Mongodb host
     *     - $config['username']   Mongodb username
     *     - $config['password']   Mongodb password
     *     - $config['database']   Mongodb database
     *     - $config['collection'] Mongodb collection
     *     - $config['prefix'] prefix for key
     * @return void
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->prefix = $config['prefix'];
        $connection_string = 'mongodb://';
        if (!empty($this->config['username']) && !empty($this->config['password'])) {
            $connection_string .= "{$this->config['username']}:{$this->config['password']}@";
        }
        $connection_string .= "{$this->config['host']}";
        $this->connection = new Mongo($connection_string);
        $this->collection = $this->connection->selectCollection($this->config['database'], $this->config['collection']);
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param  string  $key
     * @return mixed
     */
    public function get($key)
    {
        $cache_data = $this->getObject($this->prefix.$key);
        if (!$cache_data) {
            return null;
        }
        return unserialize($cache_data['cache_data']);
    }

    /**
     * Return the whole object instead of just the cache_data
     *
     * @param  string  $key
     * @return array|null
     */
    protected function getObject($key)
    {
        $cache_data = $this->collection->findOne([
            'key' => $this->prefix.$key,
        ]);
        if (is_null($cache_data)) {
            return null;
        }
        if (isset($cache_data['expire']) && time() >= $cache_data['expire']) {
            $this->forget($this->prefix.$key);
            return null;
        }
        return $cache_data;
    }

    /**
     * Store an item in the cache for a given number of minutes.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @param  int     $minutes
     * @return void
     */
    public function put($key, $value, $minutes)
    {
        $expiry = $this->expiration($minutes);
        $this->collection->updateOne(
            [
                'key' => $this->prefix.$key
            ],
            [
                '$set' => [
                    'cache_data' => serialize($value),
                    'expiry' => $expiry,
                    'ttl' => ($minutes * 60)
                ]
            ],
            [
                'upsert' => true,
                'multiple' => false
            ]
        );
    }

    /**
     * Increment the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     *
     * @throws \LogicException
     */
    public function increment($key, $value = 1)
    {
        $cache_data = $this->getObject($this->prefix.$key);
        if (!$cache_data) {
            $new_data = [
                'cache_data' => serialize($value),
                'expiry' => $this->expiration(0),
                'ttl' => $this->expiration(0)
            ];
        } else {
            $new_data = [
                'cache_data' => serialize(unserialize($cache_data['cache_data']) + $value),
                'expiry' => $this->expiration((int) ($cache_data['ttl']/60)),
                'ttl' => $cache_data['ttl']
            ];
        }
        $this->collection->updateOne(
            [
                'key' => $this->prefix.$key
            ],
            [
                '$set' => $new_data
            ],
            [
                'upsert' => true,
                'multiple' => false
            ]
        );
    }

    /**
     * Decrement the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     *
     * @throws \LogicException
     */
    public function decrement($key, $value = 1)
    {
        $cache_data = $this->getObject($this->prefix.$key);
        if (!$cache_data) {
            $new_data = [
                'cache_data' => serialize((0 - $value)),
                'expiry' => $this->expiration(0),
                'ttl' => $this->expiration(0)
            ];
        } else {
            $new_data = [
                'cache_data' => serialize(unserialize($cache_data['cache_data']) - $value),
                'expiry' => $this->expiration((int) ($cache_data['ttl']/60)),
                'ttl' => $cache_data['ttl']
            ];
        }
        $this->collection->updateOne(
            [
                'key' => $this->prefix.$key
            ],
            [
                '$set' => $new_data
            ],
            [
                'upsert' => true,
                'multiple' => false
            ]
        );
    }

    /**
     * Store an item in the cache indefinitely.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function forever($key, $value)
    {
        return $this->put($this->prefix.$key, $value, 0);
    }

    /**
     * Remove an item from the cache.
     *
     * @param  string  $key
     * @return void
     */
    public function forget($key)
    {
        $this->collection->deleteOne([
            'key' => $this->prefix.$key
        ]);
    }

    /**
     * Remove all items from the cache.
     *
     * @return void
     */
    public function flush()
    {
        $this->collection->drop();
    }

    /**
     * Get the expiration time based on the given minutes.
     *
     * @param  int  $minutes
     * @return int
     */
    protected function expiration($minutes)
    {
        if ($minutes === 0) return 9999999999;
        return time() + ($minutes * 60);
    }

    /**
     * Get the cache key prefix.
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * Retrieve multiple items from the cache by key.
     *
     * Items not found in the cache will have a null value.
     *
     * @param  array  $keys
     * @return array
     */
    public function many(array $keys)
    {
        return $this->collection->find(
            $keys,
            [
                'limit' => 0
            ]
        );
    }

    /**
     * Store multiple items in the cache for a given number of seconds.
     *
     * @param  array  $values
     * @param  int  $seconds
     * @return bool
     */
    public function putMany(array $values, $seconds)
    {
        foreach ($values as $key => $value) {
            $this->collection->updateOne(
                [
                    'key' => $this->prefix.$key
                ],
                [
                    '$set' => [
                        'cache_data' => serialize($value),
                        'ttl' => ($seconds * 60)
                    ]
                ],
                [
                    'upsert' => true,
                    'multiple' => true
                ]
            );
        }
        return true;
    }
}
