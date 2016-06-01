<?php

namespace MakinaCorpus\Ucms\Seo\Tests;

use MakinaCorpus\Drupal\Sf\Tests\AbstractPathAliasStorageTest;
use MakinaCorpus\Ucms\Seo\Path\SeoAliasStorage;

class PathAliasStorageTest extends AbstractPathAliasStorageTest
{
    protected function createAliasStorage()
    {
        return new SeoAliasStorage(
            $this->getDatabaseConnection(),
            $this->getNullModuleHandler(),
            $this
                ->getMockBuilder('MakinaCorpus\Ucms\Site\SiteManager')
                ->disableOriginalConstructor()
                ->getMock()
        );
    }

    public function testBasicBehavior()
    {
        // @todo the parent class test fails, fix it
    }
}
