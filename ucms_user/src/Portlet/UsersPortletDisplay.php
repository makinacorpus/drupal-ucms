<?php

namespace MakinaCorpus\Ucms\User\Portlet;

use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Drupal\Dashboard\Action\Action;
use MakinaCorpus\Drupal\Dashboard\Page\AbstractDisplay;
use MakinaCorpus\Ucms\Site\SiteManager;

class UsersPortletDisplay extends AbstractDisplay
{
    use StringTranslationTrait;

    /**
     * @var SiteManager
     */
    private $siteManager;

    /**
     * @var string
     */
    private $emptyMessage;

    /**
     * Default constructor
     */
    public function __construct(SiteManager $siteManager, $emptyMessage = null)
    {
        $this->siteManager = $siteManager;
        $this->emptyMessage = $emptyMessage;
    }

    /**
     * {@inheritdoc}
     */
    protected function displayAs($mode, $items)
    {
        $rows   = [];

        foreach ($items as $item) {
            /* @var $item UserInterface */
            $action = new Action($this->t("View"), 'admin/dashboard/user/' . $item->id(), null, 'eye-open');

            $roles = [];
            foreach ($item->getRoles(true) as $rid) {
                $roles[] = $this->siteManager->getAccess()->getDrupalRoleName($rid);
            }

            $rows[] = [
                check_plain($item->getAccountName()),
                format_date($item->getCreatedTime(), 'short'),
                implode('<br/>', $roles),
                $item->isActive() ? $this->t("Enabled") : $this->t("Disabled"),
                ['#theme' => 'udashboard_actions', '#actions' => [$action]],
            ];
        }

        return [
            '#theme'  => 'table',
            '#header' => [
                $this->t('Name'),
                $this->t('Creation'),
                $this->t('Roles'),
                $this->t('Status'),
                $this->t('Link'),
            ],
            '#rows'   => $rows,
            '#empty'  => $this->emptyMessage,
        ];
    }
}
