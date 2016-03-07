<?php


namespace MakinaCorpus\Ucms\Site\Page;

use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\Page\AbstractDisplay;
use MakinaCorpus\Ucms\Site\Access;


class WebmasterAdminDisplay extends AbstractDisplay
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
    protected function displayAs($mode, $accessRecords)
    {
        $rows = [];

        foreach ($accessRecords as $access) {
            $user = user_load($access->getUserId());

            $rows[] = [
                filter_xss(format_username($user)),
                check_plain($user->mail),
                ((int) $access->getRole() === Access::ROLE_WEBMASTER) ? $this->t("Webmaster") : $this->t("Contributor"),
                ($user->status == 0) ? $this->t("Disabled") : $this->t("Enabled"),
                theme('ucms_dashboard_actions', ['actions' => $this->getActions($access), 'mode' => 'icon']),
            ];
        }

        return [
            '#prefix' => '<div class="col-md-12">', // FIXME should be in theme
            '#suffix' => '</div>',                  // FIXME should be in theme
            '#theme'  => 'table',
            '#header' => [
                $this->t("Name"),
                $this->t("Email"),
                $this->t("Role"),
                $this->t("Global status"),
                '',
            ],
            '#empty'  => $this->emptyMessage,
            '#rows'   => $rows,
        ];
    }
}
