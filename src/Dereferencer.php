<?php

namespace League\JsonReference;

use League\JsonReference\ScopeResolvers\NullScopeResolver;

final class Dereferencer implements DereferencerInterface
{
    /**
     * @var LoaderManager
     */
    private $loaderManager;

    /**
     * @var ScopeResolver
     */
    private $scopeResolver;

    /**
     * Create a new Dereferencer.
     *
     * @param ScopeResolver $scopeResolver
     * @param LoaderManager $loaderManager
     */
    public function __construct(ScopeResolver $scopeResolver = null, LoaderManager $loaderManager = null)
    {
        $this->loaderManager = $loaderManager ?: new LoaderManager();
        $this->scopeResolver = $scopeResolver ?: new NullScopeResolver();

        Reference::setDereferencerInstance($this);
    }

    /**
     * {@inheritdoc}
     */
    public function dereference($schema, $uri = '')
    {
        return $this->crawl($schema, $uri, function ($schema, $pointer, $ref, $scope) {
            $resolved = new Reference($ref, $scope, is_internal_ref($ref) ? $schema : null);
            merge_ref($schema, $resolved, $pointer);
        });
    }

    /**
     * @param object|string $schema
     * @param string        $uri
     *
     * @return array
     */
    private function prepareArguments($schema, $uri)
    {
        if (is_string($schema)) {
            $uri    = $schema;
            $schema = resolve_fragment($uri, $this->loadExternalRef($uri));
            $uri    = strip_fragment($uri);
        }

        return [$schema, $uri];
    }

    /**
     * @return LoaderManager
     */
    public function getLoaderManager()
    {
        return $this->loaderManager;
    }

    /**
     * @param object   $schema
     * @param string   $uri
     * @param callable $resolver
     *
     * @return object
     */
    private function crawl($schema, $uri, callable $resolver)
    {
        list($schema, $uri) = $this->prepareArguments($schema, $uri);

        foreach (schema_extract($schema, 'League\JsonReference\is_ref') as $pointer => $ref) {
            $scope = $this->scopeResolver->resolve($schema, $pointer, $uri);

            $resolver($schema, $pointer, $ref, $scope);
        }

        return $schema;
    }

    /**
     * Load an external ref and return the JSON object.
     *
     * @param string $reference
     *
     * @return object
     */
    private function loadExternalRef($reference)
    {
        list($prefix, $path) = parse_external_ref($reference);
        return $this->loaderManager->getLoader($prefix)->load($path);
    }
}