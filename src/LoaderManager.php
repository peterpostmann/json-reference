<?php

namespace League\JsonReference;

use League\JsonReference\DecoderManager;
use League\JsonReference\Loader\CurlWebLoader;
use League\JsonReference\Loader\FileGetContentsLoader;

final class LoaderManager
{
    /**
     * @var LoaderInterface[]
     */
    private $loaders = [];
    
    /**
     * @var DecoderManager
     */
    private $decoderManager = [];

    /**
     * @param LoaderInterface[] $loaders
     */
    public function __construct(array $loaders = [], DecoderManager $decoderManager = null)
    {
        if (empty($loaders)) {
            $this->registerDefaultLoader();
        }
        
        if (empty($decoderManager)) {
            $this->decoderManager = new DecoderManager();
        }

        foreach ($loaders as $protocol => $loader) {
            $this->registerLoader($protocol, $loader);
        }
    }

    /**
     * Register a LoaderInterface for the given protocol.
     *
     * @param string          $protocol
     * @param LoaderInterface $loader
     */
    public function registerLoader($protocol, LoaderInterface $loader)
    {
        $this->loaders[$protocol] = $loader;
    }

    /**
     * Get all registered loaders, keyed by the protocol they are registered to load schemas for.
     *
     * @return LoaderInterface[]
     */
    public function getLoaders()
    {
        return $this->loaders;
    }

    /**
     * Get the loader for the given protocol.
     *
     * @param string $protocol
     *
     * @return LoaderInterface
     * @throws \InvalidArgumentException
     */
    public function getLoader($protocol)
    {
        if (!$this->hasLoader($protocol)) {
            throw new \InvalidArgumentException(sprintf('A loader is not registered for the protocol "%s"', $protocol));
        }

        return $this->loaders[$protocol];
    }

    /**
     * @param string $protocol
     *
     * @return bool
     */
    public function hasLoader($protocol)
    {
        return isset($this->loaders[$protocol]);
    }

    /**
     * Register the default loader.
     */
    private function registerDefaultLoader()
    {
        $fileLoader = new FileGetContentsLoader();
        $webLoader  = function_exists('curl_init') ? new CurlWebLoader() : $fileLoader;

        // file uri
        $this->loaders['file']  = $fileLoader;
        
        // windows path
        $this->loaders[true]    = $fileLoader;

        // relative path
        $this->loaders[false]   = $fileLoader;

        // http, https
        $this->loaders['http']  = $webLoader;
        $this->loaders['https'] = $webLoader;
    }

    /**
     * @return DecoderManager
     */
    public function getDecoderManager()
    {
        return $this->decoderManager;
    }

    /**
     * @param \League\JsonReference\DecoderManager $decoderManager
     *
     * @return \League\JsonReference\LoaderManager
     */
    public function setDecoderManager(DecoderManager $decoderManager)
    {
        $this->decoderManager = $decoderManager;

        return $this;
    }
}
