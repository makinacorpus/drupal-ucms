<?php

namespace MakinaCorpus\Ucms\User\Controller;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Drupal\Sf\Controller;
use MakinaCorpus\Ucms\Dashboard\Controller\PageControllerTrait;
use MakinaCorpus\Ucms\Site\SiteManager;

class AccountController extends Controller
{
    use PageControllerTrait;
    use StringTranslationTrait;

    /**
     * @return SiteManager
     */
    private function getSiteManager()
    {
        return $this->get('ucms_site.manager');
    }

    /**
     * User details display
     */
    public function viewInfoAction(AccountInterface $account)
    {
        $table = $this->createAdminTable('ucms_user_profile', ['user' => $account]);

        $table->addHeader($this->t("Details"));
        $table->addRow($this->t("Created at"), format_date($account->created));

        $latestConnection = $account->getLastAccessedTime();
        if ($latestConnection) {
            $latestConnection = format_date($latestConnection);
        } else {
            $latestConnection = $this->t("Never");
        }
        $table->addRow($this->t("Last connection at"), $latestConnection);

        $access = $this->getSiteManager()->getAccess();
        $roles = array_map(
            function ($roleId) use ($access) {
                return check_plain($access->getDrupalRoleName($roleId));
            },
            $account->getRoles(true)
        );

        $table->addRow($this->t("Roles"), implode('<br/>', $roles));

        return $table->render();
    }
}
