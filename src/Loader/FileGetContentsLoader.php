<?php

namespace League\JsonReference\Loader;

use League\JsonReference\DecoderManager;
use League\JsonReference\DecoderInterface;
use League\JsonReference\LoaderInterface;
use League\JsonReference\SchemaLoadingException;

final class FileGetContentsLoader implements LoaderInterface
{
    /**
     * @var DecoderManager
     */
    private $decoders;

    /**
     * @param JsonDecoderInterface|DecoderManager $decoders
     */
    public function __construct($decoders = null)
    {
        if ($decoders instanceof DecoderInterface) {
            $this->decoders = new DecoderManager([$decoders]);
        } else {
            $this->decoders = $decoders ?: new DecoderManager();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function load($uri, $defaultExtension = 'json')
    {
        $extension = isset(pathinfo($uri)['extension']) ? pathinfo($uri)['extension'] : $defaultExtension;

        set_error_handler(function () use ($uri) {
            throw SchemaLoadingException::create($uri);
        });
        $response = file_get_contents($uri);
        restore_error_handler();

        if (!$response) {
            throw SchemaLoadingException::create($uri);
        }

        return $this->decoders->getDecoder($extension)->decode($response);
    }
}
