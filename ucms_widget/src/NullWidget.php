<?php

namespace MakinaCorpus\Ucms\Widget;

use Drupal\Core\Entity\EntityInterface;

use MakinaCorpus\Ucms\Site\Site;

/**
 * Null object implementation for non-existing widget types at runtime
 */
class NullWidget implements WidgetInterface
{
    /**
     * {@inheritdoc}
     */
    public function render(EntityInterface $entity, Site $site, $options = [], $formatterOptions = [])
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOptions()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getOptionsForm($options = [])
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultFormatterOptions()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormatterOptionsForm($options = [])
    {
        return null;
    }
}
