<?php

namespace MakinaCorpus\Ucms\Contrib\Filter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

use Symfony\Component\DependencyInjection\ContainerInterface;

class MediaFilter extends FilterBase implements ContainerFactoryPluginInterface
{
    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition)
    {
        return new static(
            $configuration,
            $pluginId,
            $pluginDefinition,
            $container->get('entity.manager')
        );
    }

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * Default constructor
     *
     * @param mixed[] $configuration
     * @param string $pluginId
     * @param string $pluginDefinition
     * @param EntityManager $entityManager
     */
    public function __construct(array $configuration, $pluginId, $pluginDefinition, EntityManager $entityManager)
    {
        parent::__construct($configuration, $pluginId, $pluginDefinition);

        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function process($text, $langcode)
    {
        // @todo, seriously, implement me...
        return new FilterProcessResult($text);
    }
}
