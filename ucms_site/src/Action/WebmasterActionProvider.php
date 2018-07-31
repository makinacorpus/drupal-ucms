<?php

namespace MakinaCorpus\Ucms\Site\Action;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Ucms\Dashboard\Action\AbstractActionProvider;
use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\SiteAccessRecord;
use MakinaCorpus\Ucms\Site\SiteManager;

class WebmasterActionProvider extends AbstractActionProvider
{
    use StringTranslationTrait;

    private $siteManager;
    private $currentUser;

    /**
     * Default constructor
     */
    public function __construct(SiteManager $manager, AccountInterface $currentUser)
    {
        $this->siteManager = $manager;
        $this->currentUser = $currentUser;
    }

    /**
     * {@inheritdoc}
     */
    public function getActions($item, bool $primaryOnly = false, array $groups = []): array
    {
        /** @var \MakinaCorpus\Ucms\Site\SiteAccessRecord $item */
        $ret = [];
        $isCurrentUser = $item->getUserId() == $this->currentUser->id();

        /*
        if ($item->getRole() === Access::ROLE_WEBMASTER) {
            $path = $this->buildWebmasterUri($item, 'demote');
            $ret[] = new Action($this->t("Demote as contributor"), $path, 'dialog', 'circle-arrow-down', 50, true, true);
        } else if ($item->getRole() === Access::ROLE_CONTRIB) {
            $path = $this->buildWebmasterUri($item, 'promote');
            $ret[] = new Action($this->t("Promote as webmaster"), $path, 'dialog', 'circle-arrow-up', 50, true, true);
        }
         */

        if (!$isCurrentUser || $this->currentUser->hasPermission(Access::PERM_SITE_GOD) || $this->currentUser->hasPermission(Access::PERM_SITE_MANAGE_ALL)) {
            $ret[] = Action::create([
                'title'     => $this->t("Remove"),
                'route'     => 'ucms_site.admin.site.webmaster_delete',
                'options'   => ['site' => $item->getSiteId(), 'user' => $item->getUserId()],
                'icon'      => 'trash',
                'primary'   => true,
            ]);
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($item): bool
    {
        return $item instanceof SiteAccessRecord;
    }
}
