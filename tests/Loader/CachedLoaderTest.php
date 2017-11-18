<?php

namespace League\JsonReference\Test\Loader;

use Cache\Adapter\PHPArray\ArrayCachePool;
use Cache\Bridge\SimpleCache\SimpleCacheBridge;
use League\JsonReference\Loader\ArrayLoader;
use League\JsonReference\Loader\CachedLoader;

class CachedLoaderTest extends \PHPUnit_Framework_TestCase
{
    function test_it_caches_the_schema()
    {
        $cache  = new ArrayCachePool();
        $cache  = new SimpleCacheBridge($cache);
        $uri   = 'file://schema';
        $schema = json_decode('{"hello": "world"}');
        $loader = new CachedLoader($cache, new \League\JsonReference\Loader\ArrayLoader([$uri => $schema]));
        $loader->load($uri);
        $this->assertSame($schema, $cache->get(sha1($uri)));
    }

    function test_it_uses_the_cached_schema()
    {
        $cache  = new ArrayCachePool();
        $cache  = new SimpleCacheBridge($cache);
        $loader = new CachedLoader($cache, new ArrayLoader([]));
        $cache->set(sha1($uri = 'file://schema'), $schema = json_decode('{"hello": "world"}'));
        $result = $loader->load($uri);
        $this->assertSame($schema, $result);
    }
}
