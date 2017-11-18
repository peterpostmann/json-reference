<?php

namespace League\JsonReference\Test\Loader;

use League\JsonReference\Loader\FileGetContentsLoader;

class FileGetContentsLoaderTest extends \PHPUnit_Framework_TestCase
{
    function test_it_loads_schemas()
    {
        $loader = new FileGetContentsLoader();
        $response = $loader->load('http://localhost:1234/integer.json');
        $this->assertSame('{"type":"integer"}', json_encode($response));
    }

    /**
     * @expectedException \League\JsonReference\SchemaLoadingException
     */
    function test_it_throws_when_the_schema_is_not_found()
    {
        $loader = new FileGetContentsLoader();
        $loader->load('http://localhost:1234/unknown');
    }

    /**
     * @expectedException \League\JsonReference\SchemaLoadingException
     */
    function test_it_throws_when_the_response_is_empty()
    {
        $loader = new FileGetContentsLoader();
        $loader->load('http://localhost:1234/empty.json');
    }

    /**
     * @expectedException \League\JsonReference\SchemaLoadingException
     */
    function test_load_throws_when_the_schema_is_not_found()
    {
        $loader = new FileGetContentsLoader();
        $response = $loader->load(__DIR__ . '/not-found.json');
    }
}
