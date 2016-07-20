<?php

namespace MakinaCorpus\Ucms\Tree\Action;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\Action\AbstractActionProcessor;
use MakinaCorpus\Ucms\Seo\SeoService;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Umenu\Menu;
use MakinaCorpus\Umenu\MenuStorageInterface;

class TreeDeleteProcessor extends AbstractActionProcessor
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

        parent::__construct($this->t("Delete"), 'trash', 500, true);
    }

    public function getId()
    {
        return 'tree_delete';
    }

    public function getQuestion($items, $totalCount)
    {
        return $this->formatPlural(
            $totalCount,
            "Delete this page menu tree?",
            "Delete those @count menu trees?"
        );
    }

    public function appliesTo($item)
    {
        // You may not delete the canonical alias
        return $item instanceof Menu;
    }

    public function processAll($items)
    {
        foreach ($items as $item) {
            $this->menuStorage->delete($item->getId());
        }

        return $this->formatPlural(
            count($item),
            "Menu tree has been deleted",
            "@count menu trees have been deleted"
        );
    }

    public function getItemId($item)
    {
        return $item->getId();
    }

    public function loadItem($id)
    {
        $items = $this->menuStorage->loadWithConditions(['id' => $id]);
        if ($items) {
            return reset($items);
        }
    }
}
