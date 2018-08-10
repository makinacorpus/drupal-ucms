<?php

namespace Drupal\ucms_tree\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\Tree\EventDispatcher\SiteEventSubscriber;
use MakinaCorpus\Umenu\TreeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Display current site main menu.
 *
 * @Block(
 *   id = "ucms_tree_menu_main",
 *   admin_label = @Translation("Main site menu")
 * )
 */
class CurrentMenuBlock extends BlockBase implements ContainerFactoryPluginInterface
{
    private $siteManager;
    private $treeManager;

    /**
     * Default constructor.
     */
    public function __construct(array $configuration, $plugin_id, $plugin_definition, SiteManager $siteManager, TreeManager $treeManager)
    {
        parent::__construct($configuration, $plugin_id, $plugin_definition);

        $this->siteManager = $siteManager;
        $this->treeManager = $treeManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
    {
        return new static(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $container->get('ucms_site.manager'),
            $container->get('umenu.manager')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function build()
    {
        if ($this->siteManager->hasContext()) {
            return [
                '#theme' => 'umenu__current',
                '#tree' => $this->treeManager->buildTree(SiteEventSubscriber::getMenuName($this->siteManager->getContext()), true),
            ];
        }

        return [];
    }
}
