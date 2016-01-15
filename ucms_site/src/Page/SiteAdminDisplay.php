<?php

namespace MakinaCorpus\Ucms\Site\Page;

use MakinaCorpus\Ucms\Dashboard\Page\AbstractDisplay;
use MakinaCorpus\Ucms\Site\State;

class SiteAdminDisplay extends AbstractDisplay
{
    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'site';
    }

    /**
     * For display table mode, fetch table headers, so that the caller doing
     * the query might apply the sort by itself
     *
     * @return array
     */
    public function getTableHeaders()
    {
        return [
            ['data' => t("Type"), 'field' => 's.type'],
            ['data' => t("Title"), 'field' => 's.title'],
            ['data' => t("State"), 'field' => 's.state'],
            ['data' => t("Created"), 'field' => 's.ts_created'],
            ['data' => t("Last update"), 'field' => 's.ts_changed', 'sort' => 'desc'],
            ['data' => t("Owner"), 'field' => 'u.name'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getSupportedModes()
    {
        return [
            'table' => t("table"),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function displayAs($mode, $sites)
    {
        /* @var $sites \MakinaCorpus\Ucms\Site\Site[] */

        switch ($mode) {

            case 'table':
                $rows   = [];
                $states = State::getList();

                // Preload users, we'll need it here
                $accountMap = [];
                foreach ($sites as $site) {
                    $accountMap[$site->uid] = $site->uid;
                }
                $accountMap = user_load_multiple($accountMap);

                foreach ($sites as $site) {
                    $rows[] = [
                        check_plain($site->type),
                        check_plain($site->title),
                        check_plain($states[$site->state]),
                        format_interval($site->ts_created->getTimestamp()),
                        format_interval($site->ts_changed->getTimestamp()),
                        isset($accountMap[$site]->uid) ? format_username($accountMap[$site->uid]) : '',
                    ];
                }

                return [
                    '#prefix' => '<div class="col-md-12">', // FIXME should be in theme
                    '#suffix' => '</div>', // FIXME should be in theme
                    '#theme'  => 'table',
                    '#header' => $this->getTableHeaders(),
                    '#rows'   => $rows,
                ];
        }
    }
}
