<?php

namespace MakinaCorpus\Ucms\Site\Security;

/**
 * @todo implement me.
 */

    /**
     * Can the user publish (and unpublish) this node
     *
     * @param AccountInterface $account
     * @param NodeInterface $node
     *
     * @return boolean
     *
    public function userCanPublish(AccountInterface $account, NodeInterface $node)
    {
        if (!$node->access(Access::OP_VIEW, $account)) {
            return false; // Avoid breaking context (such as group)
        }
        if ($account->hasPermission(Access::PERM_CONTENT_GOD)) {
            return true;
        }
        if ($node->is_global && $account->hasPermission(Access::PERM_CONTENT_MANAGE_GLOBAL)) {
            return true;
        }
        if ($node->is_group && $account->hasPermission(Access::PERM_CONTENT_MANAGE_CORPORATE)) {
            return true;
        }
        if (!empty($node->site_id) && ($userSites = $this->manager->loadWebmasterSites($account))) {
            foreach ($userSites as $site) {
                if ($node->site_id == $site->id) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Can the user reference this node on one of his sites
     *
     * @param AccountInterface $account
     * @param NodeInterface $node
     *
     * @return boolean
     *
    public function userCanReference(AccountInterface $account, NodeInterface $node)
    {
        return $node->access(Access::OP_VIEW, $account) && $this->manager->getAccess()->userIsWebmaster($account);
    }

    /**
     * Can the user dereference the current content from the given site
     *
     * @param AccountInterface $account
     * @param NodeInterface $node
     * @param Site $site
     *
     * @return boolean
     *
    public function userCanDereference(AccountInterface $account, NodeInterface $node, Site $site)
    {
        return $node->access(Access::OP_VIEW, $account) && in_array($site->getId(), $node->ucms_sites) && $this->manager->getAccess()->userIsWebmaster($account, $site);
    }

    /**
     * Can user promote or unpromote this node as a group node
     *
     * @param AccountInterface $account
     * @param NodeInterface $node
     *
     * @return boolean
     *
    public function userCanPromoteToGroup(AccountInterface $account, NodeInterface $node)
    {
        return $node->access(Access::OP_VIEW, $account) && ($node->is_group || $node->is_global) && $account->hasPermission(Access::PERM_CONTENT_MANAGE_CORPORATE);
    }

    /**
     * Can user lock or unlock this node
     *
     * @param AccountInterface $account
     * @param NodeInterface $node
     *
     * @return boolean
     *
    public function userCanLock(AccountInterface $account, NodeInterface $node)
    {
        if (!$node->access(Access::OP_VIEW, $account)) {
            return false; // Avoid breaking context (such as group)
        }

        if ($node->is_group) {
            return $account->hasPermission(Access::PERM_CONTENT_MANAGE_CORPORATE);
        }

        if ($node->is_global) {
            return $account->hasPermission(Access::PERM_CONTENT_MANAGE_GLOBAL);
        }

        if ($node->site_id) {
            // Got a site !
            // @todo I must find a shortcut for this...
            return $this
                ->manager
                ->getAccess()
                ->userIsWebmaster(
                    $account,
                    $this
                        ->manager
                        ->getStorage()
                        ->findOne($node->site_id)
                )
            ;
        }

        return false;
    }

    /**
     * Can user copy this node
     *
     * @param AccountInterface $account
     * @param NodeInterface $node
     *
     * @return boolean
     *
    public function userCanDuplicate(AccountInterface $account, NodeInterface $node)
    {
        if (!$node->access(Access::OP_VIEW, $account)) {
            return false; // Avoid breaking context (such as group)
        }

        if (!$node->is_clonable) {
            return false;
        }
        if (empty($node->ucms_sites)) {
            return false;
        }

        $roles = $this->manager->getAccess()->getUserRoles($account);

        foreach (array_intersect_key($roles, array_flip($node->ucms_sites)) as $role) {
            if ($role->getRole() == Access::ROLE_WEBMASTER) {
                return true;
            }
        }

        return false;
    }
     */