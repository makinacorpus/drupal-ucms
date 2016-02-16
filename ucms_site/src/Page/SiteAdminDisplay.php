<?php

namespace MakinaCorpus\Ucms\Site\Page;

use MakinaCorpus\Ucms\Dashboard\Page\AbstractDisplay;
use MakinaCorpus\Ucms\Site\SiteState;

class SiteAdminDisplay extends AbstractDisplay
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
    protected function displayAs($mode, $sites)
    {
        /* @var $sites \MakinaCorpus\Ucms\Site\Site[] */
        $rows   = [];
        $states = SiteState::getList();

        // Preload users, we'll need it here
        $accountMap = [];
        foreach ($sites as $site) {
            $accountMap[$site->uid] = $site->uid;
        }
        $accountMap = user_load_multiple($accountMap);

        foreach ($sites as $site) {
            $rows[] = [
                check_plain($site->type),
                check_plain($site->http_host),
                check_plain($site->title),
                check_plain($states[$site->state]),
                format_interval(time() - $site->ts_created->getTimestamp()),
                format_interval(time() - $site->ts_changed->getTimestamp()),
                isset($accountMap[$site->uid]) ? format_username($accountMap[$site->uid]) : '',
                theme('ucms_dashboard_actions', ['actions' => $this->getActions($site), 'mode' => 'icon']),
            ];
        }

        return [
            '#prefix' => '<div class="col-md-12">', // FIXME should be in theme
            '#suffix' => '</div>', // FIXME should be in theme
            '#theme'  => 'table',
            '#header' => [t("Type"), t("Hostname"), t("Title"), t("State"), t("Created"), t("Last update"), t("Owner"), ''],
            '#empty'  => $this->emptyMessage,
            '#rows'   => $rows,
        ];
    }
}
