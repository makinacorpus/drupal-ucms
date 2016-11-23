<?php

namespace MakinaCorpus\Ucms\Contrib\Action;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\Action\ActionProviderInterface;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;

class SiteActionProvider implements ActionProviderInterface
{
    use StringTranslationTrait;

    private $manager;
    private $currentUser;

    /**
     * Default constructor
     *
     * @param SiteManager $manager
     */
    public function __construct(SiteManager $manager, AccountInterface $currentUser)
    {
        $this->manager = $manager;
        $this->currentUser = $currentUser;
    }

    /**
     * {inheritdoc}
     */
    public function getActions($item)
    {
        /** @var $item Site */
        $ret = [];

        $account  = $this->currentUser;
        $access   = $this->manager->getAccess();
        $urlGenerator = $this->manager->getUrlGenerator();

        if ($access->userCanOverview($account, $item)) {
            list($path, $options) = $urlGenerator->getRouteAndParams($item->getId(), 'admin/dashboard/content');
            $ret[] = new Action($this->t("Content in site"), $path, $options, 'file', 100, false, false, false, 'content');
            list($path, $options) = $urlGenerator->getRouteAndParams($item->getId(), 'admin/dashboard/media');
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
