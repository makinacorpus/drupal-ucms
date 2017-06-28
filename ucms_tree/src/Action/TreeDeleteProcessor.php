<?php

namespace MakinaCorpus\Ucms\Tree\Action;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Drupal\Calista\Action\AbstractActionProcessor;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Umenu\Menu;
use MakinaCorpus\Umenu\MenuStorageInterface;

/**
 * Delete action on tree.
 */
class TreeDeleteProcessor extends AbstractActionProcessor
{
    use StringTranslationTrait;

    private $menuStorage;
    private $siteManager;
    private $currentUser;

    /**
     * Default constructor
     *
     * @param MenuStorageInterface $menuStorage
     * @param SiteManager $siteManager
     * @param AccountInterface $currentUser
     */
    public function __construct(MenuStorageInterface $menuStorage, SiteManager $siteManager, AccountInterface $currentUser)
    {
        $this->menuStorage = $menuStorage;
        $this->siteManager = $siteManager;
        $this->currentUser = $currentUser;

        parent::__construct($this->t("Delete"), 'trash', 500, false);
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'tree_delete';
    }

    /**
     * {@inheritdoc}
     */
    public function getQuestion($items, $totalCount)
    {
        return $this->formatPlural(
            $totalCount,
            "Delete this menu tree?",
            "Delete those @count menu trees?"
        );
    }

    /**
     * {@inheritdoc}
     */
    public function appliesTo($item)
    {
        // You may not delete the canonical alias
        return $item instanceof Menu;
    }

    /**
     * {@inheritdoc}
     */
    public function processAll($items)
    {
        /** @var \MakinaCorpus\Umenu\Menu $item */
        foreach ($items as $item) {
            $this->menuStorage->delete($item->getId());
        }

        return $this->formatPlural(
            count($item),
            "Menu tree has been deleted",
            "@count menu trees have been deleted"
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
