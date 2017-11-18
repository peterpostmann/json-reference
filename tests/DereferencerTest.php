<?php

namespace League\JsonReference\Test;

use League\JsonReference\Dereferencer;
use League\JsonReference\Loader\ArrayLoader;
use League\JsonReference\Pointer;
use League\JsonReference\Reference;
use League\JsonReference\LoaderManager;
use function peterpostmann\uri\fileuri;

class DereferencerTest extends \PHPUnit_Framework_TestCase
{
    function test_it_resolves_inline_references()
    {
        $deref  = new Dereferencer();
        $uri   = __DIR__ . '/fixtures/inline-ref.json';
        $result = $deref->dereference($uri);

        $this->assertSame(json_encode($result->definitions->address), json_encode($result->properties->billing_address->resolve()));
        $this->assertSame(json_encode($result->definitions->address), json_encode($result->properties->shipping_address->resolve()));
    }

    function test_it_resolves_inline_references_when_initial_schema_used_pointer()
    {
        $deref  = new Dereferencer();
        $uri    = __DIR__ . '/fixtures/inline-ref.json?#/properties/billing_address';
        $result = $deref->dereference($uri);

        $expected = json_decode(file_get_contents(__DIR__ . '/fixtures/inline-ref.json'));
        $expected = (new Pointer($expected))
            ->get('/definitions/address');

        $this->assertEquals($expected, $result);
    }

    function test_it_resolves_remote_references()
    {
        $loader = new ArrayLoader(
            ['http://json-schema.org/draft-04/schema' => file_get_contents(__DIR__ . '/fixtures/draft4-schema.json')]
        );
        $deref  = new Dereferencer();
        $deref->getLoaderManager()->registerLoader('http', $loader);
        $deref->getLoaderManager()->registerLoader('https', $loader);
        $result = $deref->dereference('http://json-schema.org/draft-04/schema#');
        $this->assertSame($result->definitions->positiveIntegerDefault0, $result->properties->minItems->resolve());
    }

    function test_it_resolves_remote_references_without_an_id()
    {
        $deref  = new Dereferencer();
        $result = $deref->dereference('http://localhost:1234/albums.json');

        $this->assertSame('string', $result->items->properties->title->type);
    }
    
    function test_it_resolves_mixed_references()
    {
        $deref  = new Dereferencer();
        $result = $deref->dereference('http://localhost:1234/albums.yaml');

        $this->assertSame('string', $result->items->properties->title->type);
    }

    function test_it_resolve_relative_ref()
    {
        $deref  = new Dereferencer();
        $deref->dereference(json_decode('{"$ref": "' . __DIR__ . '/fixtures/draft4-schema.json"}'));
    }

    /**
     * @expectedException \League\JsonReference\SchemaLoadingException
     */
    function test_it_fails_when_resolving_a_non_existing_relative_ref()
    {
        $deref  = new Dereferencer();
        $deref->dereference(json_decode('{"$ref": "album.json"}'));
    }

    function test_it_resolves_web_remote_references_with_fragments()
    {
        $deref  = new Dereferencer();
        $result = $deref->dereference('http://localhost:1234/subSchemas.json#/relativeRefToInteger');
        $this->assertSame(['type' => 'integer'], (array) $result);
    }

    function test_it_resolves_file_remote_references_with_fragments()
    {
        $deref  = new Dereferencer();
        $uri    = __DIR__ . '/fixtures/schema.json?#/properties';
        $result = $deref->dereference($uri);
        $this->assertArrayHasKey('name', (array) $result);
    }

    function test_it_resolves_recursive_root_pointers()
    {
        $deref  = new Dereferencer();
        $uri   = __DIR__ . '/fixtures/recursive-root-pointer.json';
        $result = $deref->dereference($uri);
        $this->assertSame(
            $result->properties->foo->additionalProperties,
            $result->properties->foo->properties->foo->additionalProperties
        );
    }

    function test_it_resolves_circular_references_to_self()
    {
        $deref  = new Dereferencer();
        $uri   = __DIR__ . '/fixtures/circular-ref-self.json';
        $result = $deref->dereference($uri);

        $this->assertSame(
            '{"$ref":"#\/definitions\/thing"}',
            json_encode($result->definitions->thing)
        );
        $this->assertSame(
            $result->definitions->thing,
            $result->definitions->thing->resolve()->resolve()->resolve()->resolve(),
            'You should be able to resolve recursive definitions to any depth'
        );
    }

    function test_it_resolves_circular_references_to_parent()
    {
        $deref  = new Dereferencer();
        $uri   = __DIR__ . '/fixtures/circular-ref-parent.json';
        $result = $deref->dereference($uri);
        $ref    = $result
            ->definitions
            ->person
            ->properties
            ->spouse
            ->type
            ->resolve();

        $this->assertSame(json_encode($result->definitions->person), json_encode($ref));
    }

    function test_it_resolves_indirect_circular_references()
    {
        $deref  = new Dereferencer();
        $uri   = __DIR__ . '/fixtures/circular-ref-indirect.json';
        $result = $deref->dereference($uri);

        $this->assertSame(
            $result->definitions->parent->properties->children->items->properties->name,
            $result->definitions->child->properties->name
        );
    }

    function test_resolves_references_in_arrays()
    {
        $deref  = new Dereferencer();
        $uri   = __DIR__ . '/fixtures/array-ref.json';
        $result = $deref->dereference($uri);
        $this->assertSame($result->items[0], $result->items[1]->resolve());
    }

    function test_dereferences_properties_that_begin_with_a_slash()
    {
        $deref  = new Dereferencer();
        $uri   = __DIR__ . '/fixtures/slash-property.json';
        $result = $deref->dereference($uri);
        $slashProperty = '/slash-item';
        $this->assertSame($result->$slashProperty->key, $result->item->key);
    }

    function test_it_dereferences_properties_with_tilde_in_name()
    {
        $deref  = new Dereferencer();
        $uri   = __DIR__ . '/fixtures/tilde-property.json';
        $result = $deref->dereference($uri);
        $tildeProperty = 'tilde~item';
        $this->assertSame($result->$tildeProperty->key, $result->item->key);
    }

    function test_it_ignores_references_that_are_not_strings()
    {
        $deref  = new Dereferencer();
        $uri   = __DIR__ . '/fixtures/property-named-ref.json';
        $result = $deref->dereference($uri);

        $this->assertTrue(is_object($result->properties->{'$ref'}));
        $this->assertSame($result->properties->{'$ref'}->description, 'The name of the property is $ref, but it\'s not a reference.');
    }

    function test_it_resolves_relative_scope_against_an_id()
    {
        $deref = Dereferencer::draft4();
        $result = $deref->dereference(json_decode('{"id": "http://localhost:1234/test.json", "properties": {"album": {"$ref": "album.json"}}}'));
        $this->assertSame('object', $result->properties->album->type);
    }

    function test_it_resolves_circular_external_references()
    {
        $deref  = new Dereferencer();
        $uri   = __DIR__ . '/fixtures/circular-ext-ref.json';
        $result = $deref->dereference($uri);
        $this->assertInstanceOf(Reference::class, $result->properties->rating);
        $this->assertFalse($result->properties->rating->additionalProperties);
        $this->assertFalse($result->properties->rating->properties->rating->additionalProperties);
    }

    function test_it_returns_serializable_schemas()
    {
        $deref  = new Dereferencer();
        $uri   = __DIR__ . '/fixtures/inline-ref.json';
        $result = $deref->dereference($uri);

        $this->assertEquals($result, unserialize(serialize($result)));
    }
    
    function test_it_returns_loader_manager()
    {
        $deref  = new Dereferencer();
        $this->assertInstanceOf(LoaderManager::class, $deref->getLoaderManager());       
    }
}
