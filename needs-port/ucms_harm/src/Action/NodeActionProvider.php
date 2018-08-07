<?php

namespace MakinaCorpus\Ucms\Harm\Action;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;
use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\Action\ActionProviderInterface;
use MakinaCorpus\Ucms\Site\Access;

class NodeActionProvider implements ActionProviderInterface
{
    use StringTranslationTrait;

    private $account;

    /**
     * Default constructor
     *
     * @param AccountInterface $account
     */
    public function __construct(AccountInterface $account)
    {
        $this->account = $account;
    }

    /**
     * {inheritdoc}
     */
    public function getActions($item)
    {
        $ret = [];

        /* @var $item NodeInterface */
        if ($item->is_global && user_access(Access::PERM_CONTENT_MANAGE_CORPORATE)) {
            $ret[] = Action::create([
                'title'     => $item->is_group ? $this->t("Remove from group contents") : $this->t("Define as group content"),
                'uri'       => 'node/' . $item->id() . ($item->is_group ? '/unmake-group' : '/make-group'),
                'options'   => 'dialog',
                'icon'      => 'briefcase',
                'primary'   => false,
                'priority'  => -30,
                'redirect'  => true,
                'group'     => 'edit',
            ]);
        }

        return $ret;
    }

    /**
     * {inheritdoc}
     */
    public function supports($item)
    {
        return $item instanceof NodeInterface;
    }
}
