<?php

namespace MakinaCorpus\Ucms\Tree\Action;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Drupal\Calista\Action\AbstractActionProcessor;
use MakinaCorpus\Ucms\Seo\SeoService;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Umenu\Menu;
use MakinaCorpus\Umenu\MenuStorageInterface;

class TreeSetMainProcessor extends AbstractActionProcessor
{
    use StringTranslationTrait;

    private $menuStorage;
    private $siteManager;
    private $currentUser;

    /**
     * Default constructor
     *
     * @param SeoService $service
     * @param AccountInterface $currentUser
     */
    public function __construct(MenuStorageInterface $menuStorage, SiteManager $siteManager, AccountInterface $currentUser)
    {
        $this->menuStorage = $menuStorage;
        $this->siteManager = $siteManager;
        $this->currentUser = $currentUser;

        parent::__construct($this->t("Set as main menu"), 'pushpin', 100, false);
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'tree_set_main';
    }

    /**
     * {@inheritdoc}
     */
    public function getQuestion($items, $totalCount)
    {
        return $this->formatPlural(
            $totalCount,
            "Set this menu as main site menu?",
            "Set those @count menus as main site menu?"
        );
    }

    /**
     * {@inheritdoc}
     */
    public function appliesTo($item)
    {
        // You may not delete the canonical alias
        return $item instanceof Menu && ((bool)$item->getSiteId());
    }

    /**
     * {@inheritdoc}
     */
    public function processAll($items)
    {
        foreach ($items as $item) {
            $this->menuStorage->toggleMainStatus($item->getName(), true);
        }

        return $this->formatPlural(
            count($item),
            "Menu has been set as main site menu",
            "@count menus have been set as main site menu"
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getItemId($item)
    {
        return $item->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function loadItem($id)
    {
        return $this->menuStorage->load($id);
    }
}
