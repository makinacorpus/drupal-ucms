<?php

namespace MakinaCorpus\Ucms\Search\Tests;

use Drupal\node\Node;

use MakinaCorpus\Ucms\Search\NodeIndexer;

class NodeIndexerTest extends AbstractElasticTest
{
    protected function getIndexerParams($index)
    {
        return [
            $index,
            $this->getClient(),
            $this->getDatabaseConnection(),
            $this->getDrupalContainer()->get('entity.manager'),
            $this->getNullModuleHandler(),
            $this->getAliasMap()[$index],
        ];
    }

    public function testNodeIndexerChain()
    {
        $storage = $this->getIndexStorage();

        $indexer = $storage->indexer();
        $this->assertInstanceOf('\MakinaCorpus\Ucms\Search\NodeIndexerChain', $indexer);

        $aIndexer = $this
            ->getMockBuilder('\MakinaCorpus\Ucms\Search\NodeIndexer')
            ->setConstructorArgs($this->getIndexerParams('a'))
            ->setMethods(['matches'])
            ->getMock()
        ;
        $aIndexer->method('matches')->willReturn(false);
        $aIndexer->expects($this->exactly(2))->method('matches');

        $bIndexer = $this
            ->getMockBuilder('\MakinaCorpus\Ucms\Search\NodeIndexer')
            ->setConstructorArgs($this->getIndexerParams('b'))
            ->setMethods(['matches'])
            ->getMock()
        ;
        $bIndexer->method('matches')->willReturn(true);
        $bIndexer->expects($this->exactly(2))->method('matches');

        $cIndexer = $this
            ->getMockBuilder('\MakinaCorpus\Ucms\Search\NodeIndexer')
            ->setConstructorArgs($this->getIndexerParams('c'))
            ->setMethods(['matches'])
            ->getMock()
        ;
        $cIndexer->method('matches')->willReturn(false);
        // The previous one returns true, so this should never be ed.
        $cIndexer->expects($this->never())->method('matches');

        /* @var $indexer \MakinaCorpus\Ucms\Search\NodeIndexerChain */
        $indexer->addIndexer('a', $aIndexer);
        $indexer->addIndexer('b', $bIndexer);
        $indexer->addIndexer('c', $cIndexer);

        $node42 = new Node();
        $node42->nid = 42;
        $node42->setTitle('some title');
        $node42->setPublished(true);

        $this->assertTrue($indexer->matches($node42));
        $this->assertTrue($indexer->matches($node42));
    }
}
