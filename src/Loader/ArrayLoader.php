<?php

namespace League\JsonReference\Loader;

use League\JsonReference\DecoderManager;
use League\JsonReference\DecoderInterface;
use League\JsonReference\LoaderInterface;
use League\JsonReference\SchemaLoadingException;

final class ArrayLoader implements LoaderInterface
{
    /**
     * @var array
     */
    private $schemas;

    /**
     * @var DecoderManager
     */
    private $decoders;

    /**
     * @param array $schemas  A map of schemas where path => schema.The schema should be a string or the
     *                        object resulting from a json_decode call.
     *
     * @param JsonDecoderInterface|DecoderManager $decoders
     */
    public function __construct(array $schemas, $decoders = null)
    {
        $this->schemas = $schemas;
        
        if ($decoders instanceof DecoderInterface) {
            $this->decoders = new DecoderManager([$decoders]);
        } else {
            $this->decoders = $decoders ?: new DecoderManager();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function load($uri, $extension = 'json')
    {
        if (!array_key_exists($uri, $this->schemas)) {
            throw SchemaLoadingException::notFound($uri);
        }

        $schema = $this->schemas[$uri];

        if (is_string($schema)) {
            return $this->schemas[$uri] = $this->decoders->getDecoder($extension)->decode($schema);
        } elseif (is_object($schema)) {
            return $schema;
        } else {
            throw SchemaLoadingException::create($uri);
        }
    }
}
