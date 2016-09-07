<?php

namespace MakinaCorpus\Ucms\User\Controller;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Drupal\Sf\Controller;
use MakinaCorpus\Ucms\Dashboard\Controller\PageControllerTrait;

class AccountController extends Controller
{
    use PageControllerTrait;
    use StringTranslationTrait;

    /**
     * @return AccountInterface
     */
    private function getCurrentUser()
    {
        return $this->get('current_user');
    }

    /**
     * Debug screen that displays current user grants.
     */
    public function viewGrantsAction(AccountInterface $account)
    {
        if (!$account->isAuthenticated() || $account->id() !== $this->getCurrentUser()->id()) {
            throw $this->createAccessDeniedException();
        }

        $table = $this->createAdminTable('user_grants');

        $table->addHeader($this->t("Grants"), 'basic');

        foreach (node_access_grants('view', $account) as $realm => $gids) {
            $table->addRow($realm, implode(', ', $gids));
        }

        return $table->render();
    }
}
