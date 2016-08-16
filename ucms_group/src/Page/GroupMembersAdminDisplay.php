<?php

namespace MakinaCorpus\Ucms\Group\Page;

use MakinaCorpus\Ucms\Dashboard\Page\AbstractDisplay;

class GroupMembersAdminDisplay extends AbstractDisplay
{
    /**
     * @var string
     */
    private $emptyMessage;

    /**
     * Default constructor
     */
    public function __construct($emptyMessage = null)
    {
        $this->emptyMessage = $emptyMessage;
    }

    /**
     * {@inheritdoc}
     */
    protected function displayAs($mode, $members)
    {
        $rows = [];

        /** @var \MakinaCorpus\Ucms\Group\GroupMember $member */
        foreach ($members as $member) {
            /** @var \Drupal\user\UserInterface $user */
            $user = user_load($member->getUserId());

            $rows[] = [
                $user->getDisplayName(),
                check_plain($user->getEmail()),
                $user->isActive() ? $this->t("Enabled") : $this->t("Disabled"),
                theme('ucms_dashboard_actions', ['actions' => $this->getActions($member), 'mode' => 'icon']),
            ];
        }

        $text = check_plain($this->t("This is the global status for across the whole platform. Is a user is disabled, he/she won't be able to log in."));

        return [
            '#prefix' => '<div class="col-md-12">', // FIXME should be in theme
            '#suffix' => '</div>',                  // FIXME should be in theme
            '#theme'  => 'table',
            '#header' => [
                $this->t("Name"),
                $this->t("Email"),
                // FIXME should be in theme
                $this->t("Global status") .' <span title="' . $text . '" class="glyphicon glyphicon-question-sign"></span>',
                '',
            ],
            '#empty'  => $this->emptyMessage,
            '#rows'   => $rows,
        ];
    }
}
