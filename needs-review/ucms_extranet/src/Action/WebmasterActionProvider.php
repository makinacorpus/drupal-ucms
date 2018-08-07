<?php


namespace MakinaCorpus\Ucms\Extranet\Action;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Session\AccountInterface;

use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Extranet\ExtranetAccess;
use MakinaCorpus\Ucms\Site\Action\AbstractWebmasterActionProvider;
use MakinaCorpus\Ucms\Site\SiteManager;


class WebmasterActionProvider extends AbstractWebmasterActionProvider
{
    /**
     * @var EntityManager
     */
    protected $entityManager;


    /**
     * Constructor.
     */
    public function __construct(
        SiteManager $siteManager,
        EntityManager $entityManager,
        AccountInterface $currentUser
    ) {
        $this->entityManager = $entityManager;
        parent::__construct($siteManager, $currentUser);
    }


    /**
     * {@inheritdoc}
     */
    public function getActions($item)
    {
        if ($item->getUserId() == $this->currentUser->id()) {
            return [];
        }

        $actions = [];

        /** @var \Drupal\user\UserInterface */
        $account = $this->entityManager->getStorage('user')->load($item->getUserId());

        if (
            $item->getRole() === ExtranetAccess::ROLE_EXTRANET_MEMBER &&
            $account->getLastLoginTime() == 0 &&
            $account->isBlocked()
        ) {
            // Accept action
            $path = $this->buildWebmasterUri($item, 'accept');
            $actions[] = new Action($this->t("Accept registration"), $path, 'dialog', 'ok-circle', 50, true, true);
            // Reject action
            $path = $this->buildWebmasterUri($item, 'reject');
            $actions[] = new Action($this->t("Reject registration"), $path, 'dialog', 'remove-circle', 50, true, true);
        } else {
            // Change role action
            $actions[] = $this->createChangeRoleAction($item);
            // Delete action
            $actions[] = $this->createDeleteAction($item);
        }

        return $actions;
    }


    /**
     * {@inheritdoc}
     */
    public function supports($item)
    {
        $roles = [
            ExtranetAccess::ROLE_EXTRANET_CONTRIB,
            ExtranetAccess::ROLE_EXTRANET_MEMBER,
        ];
        return parent::supports($item) && in_array($item->getRole(), $roles);
    }
}


