<?php

namespace League\JsonReference\Loader;

use League\JsonReference\LoaderInterface;
use League\JsonReference\SchemaLoadingException;

/**
 * This loader takes two other loaders as constructor parameters, and will
 * attempt to load from the first loader before deferring to the second loader.
 * This is useful when you would like to use multiple loaders for the same protocol.
 */
final class ChainedLoader implements LoaderInterface
{
    /**
     * @var LoaderInterface
     */
    private $firstLoader;

    /**
     * @var LoaderInterface
     */
    private $secondLoader;

    /**
     * @param \League\JsonReference\LoaderInterface $firstLoader
     * @param \League\JsonReference\LoaderInterface $secondLoader
     */
    public function __construct(LoaderInterface $firstLoader, LoaderInterface $secondLoader)
    {
        $this->firstLoader  = $firstLoader;
        $this->secondLoader = $secondLoader;
    }

    /**
     * {@inheritdoc}
     */
    public function load($uri)
    {
        try {
            return $this->firstLoader->load($uri);
        } catch (SchemaLoadingException $e) {
            return $this->secondLoader->load($uri);
        }
    }
}
