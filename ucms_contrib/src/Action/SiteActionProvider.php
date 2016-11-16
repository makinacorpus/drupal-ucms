<?php

namespace MakinaCorpus\Ucms\Contrib\Action;

use MakinaCorpus\ACL\Permission;
use MakinaCorpus\Ucms\Dashboard\Action\AbstractActionProvider;
use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;

class SiteActionProvider extends AbstractActionProvider
{
    private $manager;

    /**
     * Default constructor
     *
     * @param SiteManager $manager
     */
    public function __construct(SiteManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * {inheritdoc}
     */
    public function getActions($item)
    {
        /** @var $item Site */
        $ret = [];

        if ($this->isGranted(Permission::OVERVIEW, $item)) {
            list($path, $options) = $this->manager->getUrlInSite($item->getId(), 'admin/dashboard/content');
            $ret[] = new Action($this->t("Content in site"), $path, $options, 'file', 100, false, false, false, 'content');
            list($path, $options) = $this->manager->getUrlInSite($item->getId(), 'admin/dashboard/media');
            $ret[] = new Action($this->t("Medias in site"), $path, $options, 'picture', 100, false, false, false, 'content');
        }

        return $ret;
    }

    /**
     * {inheritdoc}
     */
    public function supports($item)
    {
        return $item instanceof Site;
    }
}
