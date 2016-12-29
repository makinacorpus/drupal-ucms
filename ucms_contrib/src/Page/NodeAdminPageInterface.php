<?php

namespace MakinaCorpus\Ucms\Contrib\Page;

use Drupal\Core\Session\AccountInterface;

use MakinaCorpus\Ucms\Dashboard\Page\PageTypeInterface;

interface NodeAdminPageInterface extends PageTypeInterface
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
