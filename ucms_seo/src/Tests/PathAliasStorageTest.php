<?php

namespace MakinaCorpus\Ucms\Seo\Tests;

use MakinaCorpus\Drupal\Sf\Container\Tests\AbstractPathAliasStorageTest;
use MakinaCorpus\Ucms\Seo\Path\SeoAliasStorage;

class PathAliasStorageTest extends AbstractPathAliasStorageTest
{
    protected function createAliasStorage()
    {
        return new SeoAliasStorage(
            $this->getDatabaseConnection(),
            $this->getNullModuleHandler()
        );
    }
}
