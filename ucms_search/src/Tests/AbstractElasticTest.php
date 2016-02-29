<?php

namespace MakinaCorpus\Ucms\Search\Tests;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;

use MakinaCorpus\Drupal\Sf\Container\Tests\AbstractDrupalTest;
use MakinaCorpus\Ucms\Search\IndexStorage;

abstract class AbstractElasticTest extends AbstractDrupalTest
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var string[]
     */
    private $aliases = [];

    /**
     * @var IndexStoragee
     */
    private $storage;

    /**
     * Get elastic host configuration
     *
     * @return string
     */
    final private function getElasticHost()
    {
        return getenv('ELASTIC_HOST');
    }

    /**
     * Get alias map
     *
     * @return string[]
     */
    final protected function getAliasMap()
    {
        if (!$this->aliases) {
            foreach (['a', 'b', 'c'] as $index) {
                $this->aliases[$index] = 'phpunit_' . uniqid();
            }
        }

        return $this->aliases;
    }

    /**
     * Get elastic client
     *
     * @return Client
     */
    final protected function getClient()
    {
        if (!$this->client) {
            $this->client = ClientBuilder::fromConfig([
                'hosts' => [
                    $this->getElasticHost(),
                ],
            ]);
        }

        return $this->client;
    }

    /**
     * Get index storage
     *
     * @return IndexStorage
     */
    final protected function getIndexStorage()
    {
        if (!$this->storage) {
            $this->storage = new IndexStorage(
                $this->getClient(),
                $this->getDatabaseConnection(),
                $this->getNullCacheBackend(),
                $this->getDrupalContainer()->get('entity.manager'),
                $this->getNullModuleHandler(),
                $this->getAliasMap()
            );
        }

        return $this->storage;
    }

    /**
     * Get indices mapping parameters, this can be changed by the implementor
     *
     * @return string[]
     */
    protected function getIndicesParams()
    {
        return [
            'settings' => [
                'number_of_shards'    => 1,
                'number_of_replicas'  => 0,
            ],
            'mapping' => [
                'node' => [
                    'properties' => [
                        'title' => [
                            'type'        => 'string',
                            'analyzer'    => 'standard',
                            'term_vector' => 'yes',
                            'copy_to'     => 'combined',
                        ],
                        'body' => [
                            'type'        => 'string',
                            'analyzer'    => 'standard',
                            'term_vector' => 'yes',
                            'copy_to'     => 'combined',
                        ],
                        'combined' => [
                            'type'        => 'string',
                            'analyzer'    => 'standard',
                            'term_vector' => 'yes',
                        ],
                        'status' => [
                            'type' => 'integer',
                        ],
                        'id' => [
                            'type' => 'long',
                        ],
                        'owner' => [
                            'type' => 'long',
                        ],
                        'created' => [
                            'type' => 'date',
                        ],
                        'updated' => [
                            'type' => 'date',
                        ],
                        'tags' => [
                            'type' => 'integer',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        if (!$this->getElasticHost()) {
            $this->markTestSkipped("You must set up the ELASTIC_HOST phpunit.xml php value");

            return;
        }

        parent::setUp();

        $namespace = $this->getClient()->indices();

        foreach ($this->getAliasMap() as $realname) {

            if ($namespace->exists(['index' => $realname])) {
                  $namespace->delete(['index' => $realname]);
            }

            $namespace->create([
                'index' => $realname,
                'body'  => $this->getIndicesParams(),
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        $namespace = $this->getClient()->indices();

        foreach ($this->getAliasMap() as $realname) {

            if ($namespace->exists(['index' => $realname])) {
                $namespace->delete(['index' => $realname]);
            }
        }
    }
}
