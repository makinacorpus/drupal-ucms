<?php


namespace MakinaCorpus\Ucms\User\Page;

use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\Page\AbstractDisplay;


class UserAdminDisplay extends AbstractDisplay
{
    use StringTranslationTrait;


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
     * @todo Detect for each user if it's a webmaster.
     */
    protected function displayAs($mode, $users)
    {
        $rows = [];

        foreach ($users as $user) {
            $rows[] = [
                check_plain($user->mail),
                check_plain(format_username($user)),
                ($user->status == 0) ? $this->t("Disabled") : $this->t("Enabled"),
                '@todo', //(???) ? $this->t("Yes") : $this->t("No"),
                format_interval(time() - $user->created),
                format_interval(time() - $user->login),
                theme('ucms_dashboard_actions', ['actions' => $this->getActions($user), 'mode' => 'icon']),
            ];
        }

        return [
            '#prefix' => '<div class="col-md-12">', // FIXME should be in theme
            '#suffix' => '</div>',                  // FIXME should be in theme
            '#theme'  => 'table',
            '#header' => [
                $this->t("Email"),
                $this->t("Name"),
                $this->t("Status"),
                $this->t("Is webmaster?"),
                $this->t("Created"),
                $this->t("Last connection"),
                ''
            ],
            '#empty'  => $this->emptyMessage,
            '#rows'   => $rows,
        ];
    }
}
