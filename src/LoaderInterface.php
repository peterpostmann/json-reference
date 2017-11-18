<?php

namespace League\JsonReference;

interface LoaderInterface
{
    /**
     * Load the json schema from the given path.
     *
     * @param string $uri The URI to load
     *
     * @return object The object resulting from a json_decode of the loaded path.
     * @throws \League\JsonReference\SchemaLoadingException
     */
    public function load($uri);
}
