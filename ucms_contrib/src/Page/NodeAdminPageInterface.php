<?php

namespace MakinaCorpus\Ucms\Contrib\Page;

use Drupal\Core\Session\AccountInterface;
use MakinaCorpus\Calista\DependencyInjection\PageDefinitionInterface;

interface NodeAdminPageInterface extends PageDefinitionInterface
{
    /**
     * Check for access to this page
     *
     * @param AccountInterface $account
     *
     * @return bool
     */
    public function userIsGranted(AccountInterface $account);
}
