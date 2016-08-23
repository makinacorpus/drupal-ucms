<?php

namespace MakinaCorpus\Ucms\Widget\Tests;

use Drupal\Core\Entity\EntityInterface;

use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Widget\WidgetInterface;

class HelloWorldWidget implements WidgetInterface
{
    /**
     * {@inheritdoc}
     */
    public function render(EntityInterface $entity, Site $site, $options = [], $formatterOptions = [])
    {
        if ($formatterOptions['strong']) {
            return '<p>Hello, <strong>' . check_plain($options['name']) . '&nbsp;!</strong></p>';
        }
        return '<p>Hello, ' . check_plain($options['name']) . '&nbsp;!</p>';
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOptions()
    {
        return [
            'name' => "World",
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getOptionsForm($options = [])
    {
        return [
            'name' => [
                '#title'          => "Name",
                '#type'           => 'textfield',
                '#default_value'  => $options['name'],
                '#required'       => true,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultFormatterOptions()
    {
        return [
            'strong' => false,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormatterOptionsForm($options = [])
    {
        return [
            'strong' => [
                '#title'          => "Display name in bold text",
                '#type'           => 'checkbox',
                '#default_value'  => $options['strong'],
            ],
        ];
    }
}
