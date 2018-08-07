<?php

namespace MakinaCorpus\Ucms\Seo\Path;

use Drupal\Core\Path\AliasManagerInterface;

/**
 * We don't want Drupal to query anything anymore now that we have a full
 * fledge custom alias handling; let's not allow it any chance.
 */
class NullDrupalPathAliasManager implements AliasManagerInterface
{
    /**
     * {@inheritdoc}
     */
    public function getPathByAlias($alias, $langcode = null)
    {
        return $alias;
    }

    /**
     * {@inheritdoc}
     */
    public function getAliasByPath($path, $langcode = null)
    {
        return $path;
    }

    /**
     * {@inheritdoc}
     */
    public function cacheClear($source = null)
    {
    }
}
