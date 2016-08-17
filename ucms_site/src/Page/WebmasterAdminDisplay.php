<?php

namespace MakinaCorpus\Ucms\Site\Page;

use MakinaCorpus\Ucms\Dashboard\Page\AbstractDisplay;
use MakinaCorpus\Ucms\Site\SiteManager;

class WebmasterAdminDisplay extends AbstractDisplay
{
    /**
     * @var SiteManager
     */
    private $manager;

    /**
     * @var string
     */
    private $emptyMessage;


    /**
     * Default constructor
     */
    public function __construct(SiteManager $manager, $emptyMessage = null)
    {
        $this->manager = $manager;
        $this->emptyMessage = $emptyMessage;
    }


    /**
     * {@inheritdoc}
     */
    protected function displayAs($mode, $items)
    {
        $rows = [];
        $relativeRoles = $this->manager->getAccess()->collectRelativeRoles();

        /** @var \MakinaCorpus\Ucms\Site\SiteAccessRecord $item */
        foreach ($items as $item) {
            $rows[] = [
                $item->getDisplayName(),
                check_plain($item->getEmail()),
                $relativeRoles[$item->getRole()],
                //((int)$item->getRole() === Access::ROLE_WEBMASTER) ? $this->t("Webmaster") : $this->t("Contributor"),
                ($item->isBlocked()) ? $this->t("Disabled") : $this->t("Enabled"),
                theme('ucms_dashboard_actions', ['actions' => $this->getActions($item), 'mode' => 'icon']),
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
                $this->t("Role"),
                // FIXME should be in theme
                $this->t("Global status") .' <span title="' . $text . '" class="glyphicon glyphicon-question-sign"></span>',
                '',
            ],
            '#empty'  => $this->emptyMessage,
            '#rows'   => $rows,
        ];
    }
}
