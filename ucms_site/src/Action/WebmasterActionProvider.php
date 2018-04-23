<?php

namespace MakinaCorpus\Ucms\Site\Action;

use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\Action\AbstractWebmasterActionProvider;

class WebmasterActionProvider extends AbstractWebmasterActionProvider
{
    /**
     * {@inheritdoc}
     */
    public function getActions($item)
    {
        if ($item->getUserId() == $this->currentUser->id()) {
            return [];
        }

        $relativeRoles = $this->manager->getAccess()->collectRelativeRoles();

        $actions = [];

        if (count($relativeRoles) > 2) {
            $actions[] = $this->createChangeRoleAction($item);
        } else {
            if ($item->getRole() === Access::ROLE_WEBMASTER) {
                $path = $this->buildWebmasterUri($item, 'demote');
                $actions[] = new Action($this->t("Demote as contributor"), $path, 'dialog', 'circle-arrow-down', 50, true, true);
            }
            elseif ($item->getRole() === Access::ROLE_CONTRIB) {
                $path = $this->buildWebmasterUri($item, 'promote');
                $actions[] = new Action($this->t("Promote as webmaster"), $path, 'dialog', 'circle-arrow-up', 50, true, true);
            }
        }

        $actions[] = $this->createDeleteAction($item);

        return $actions;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($item)
    {
        // We act only on the roles known by ucms_site and let other modules
        // provide actions for their own roles.
        $roles = [Access::ROLE_WEBMASTER, Access::ROLE_CONTRIB];

        return parent::supports($item) && in_array($item->getRole(), $roles);
    }
}
