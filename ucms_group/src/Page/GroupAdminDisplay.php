<?php

namespace MakinaCorpus\Ucms\Group\Page;

use MakinaCorpus\Ucms\Dashboard\Page\AbstractDisplay;
use MakinaCorpus\Ucms\Group\GroupManager;

class GroupAdminDisplay extends AbstractDisplay
{
    private $emptyMessage;
    private $groupManager;

    /**
     * Default constructor
     */
    public function __construct(GroupManager $groupManager, $emptyMessage = null)
    {
        $this->groupManager = $groupManager;
        $this->emptyMessage = $emptyMessage;
    }

    /**
     * {@inheritdoc}
     */
    protected function displayAs($mode, $groups)
    {
        $rows   = [];

        foreach ($groups as $group) {
            /** @var \MakinaCorpus\Ucms\Group\Group $group */
            $rows[] = [
                $group->getId(),
                check_plain($group->getTitle()),
                $group->isMeta() ? $this->t("yes") : '',
                $group->isGhost() ? $this->t("invisible") : $this->t("visible"),
                format_interval(time() - $group->createdAt()->getTimestamp()),
                format_interval(time() - $group->changedAt()->getTimestamp()),
                theme('ucms_dashboard_actions', ['actions' => $this->getActions($group), 'mode' => 'icon']),
            ];
        }

        return [
            '#prefix' => '<div class="col-md-12">', // FIXME should be in theme
            '#suffix' => '</div>', // FIXME should be in theme
            '#theme'  => 'table',
            '#header' => [
                $this->t("Id."),
                $this->t("Title"),
                $this->t("Is default"),
                $this->t("Default status"),
                $this->t("Created"),
                $this->t("Last update"),
                '',
            ],
            '#empty'  => $this->emptyMessage,
            '#rows'   => $rows,
        ];
    }
}
