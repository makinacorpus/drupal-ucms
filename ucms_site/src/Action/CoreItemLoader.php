<?php

namespace MakinaCorpus\Ucms\Site\Action;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use MakinaCorpus\Ucms\Dashboard\Action\ItemIdentity;
use MakinaCorpus\Ucms\Dashboard\Action\ItemLoaderInterface;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteAccessRecord;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Umenu\Menu;
use MakinaCorpus\Umenu\MenuStorageInterface;

final class CoreItemLoader implements ItemLoaderInterface
{
    private $entityTypeManager;
    private $menuStorage;
    private $siteManager;

    public function __construct(SiteManager $siteManager, EntityTypeManagerInterface $entityTypeManager, MenuStorageInterface $menuStorage)
    {
        $this->entityTypeManager = $entityTypeManager;
        $this->menuStorage = $menuStorage;
        $this->siteManager = $siteManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdFrom($item)
    {
        if ($item instanceof Site) {
            return new ItemIdentity('site', $item->getId());
        }
        if ($item instanceof SiteAccessRecord) {
            return new ItemIdentity('site_access', $item->generateUniqueId());
        }
        if ($item instanceof EntityInterface) {
            return new ItemIdentity($item->getEntityTypeId(), $item->id());
        }
        if ($item instanceof Menu) {
            return new ItemIdentity('umenu', $item->getId());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function load(ItemIdentity $identity)
    {
        switch ($identity->type) {

            case 'site':
                return $this->siteManager->getStorage()->findOne($identity->id);

            case 'site_access':
                return SiteAccessRecord::createPartial(...explode('-', $identity->id));

            case 'menu':
                return; // Not implemented yet.

            default:
                try {
                    return $this->entityTypeManager->getStorage($identity->type)->load($identity->id);
                    // Exception means type does not exist, just pass.
                } catch (PluginNotFoundException $e) {}
                break;
        }
    }
}