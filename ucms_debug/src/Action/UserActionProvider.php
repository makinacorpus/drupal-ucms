<?php

namespace MakinaCorpus\Ucms\Debug\Action;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Session\AccountInterface;

use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\Action\ActionProviderInterface;
use MakinaCorpus\Ucms\Debug\Access;

class UserActionProvider implements ActionProviderInterface
{
    use StringTranslationTrait;

    private $currentUser;

    /**
     * Default constructor
     *
     * @param AccountInterface $currentUser
     */
    public function __construct(AccountInterface $currentUser)
    {
        $this->currentUser = $currentUser;
    }

    /**
     * {inheritdoc}
     */
    public function getActions($item)
    {
        $ret = [];

        /** @var \Drupal\Core\Session\AccountInterface $item */
        if ($this->currentUser->hasPermission(Access::PERM_ACCESS_DEBUG)) {
            $ret[] = new Action($this->t("Debug information"), 'admin/dashboard/user/' . $item->id() . '/debug', [], 'cog', 1024, false, false, false, 'debug');
        }

        return $ret;
    }

    /**
     * {inheritdoc}
     */
    public function supports($item)
    {
        return $item instanceof AccountInterface;
    }
}
