<?php

namespace MakinaCorpus\Ucms\Debug\Controller;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;

use MakinaCorpus\Drupal\Dashboard\Controller\PageControllerTrait;
use MakinaCorpus\Drupal\Sf\Controller;
use MakinaCorpus\Ucms\Debug\Access;

class DebugInfoController extends Controller
{
    use PageControllerTrait;
    use StringTranslationTrait;

    /**
     * Debug screen that displays user grants.
     */
    public function viewAccountGrantsAction(AccountInterface $account)
    {
        $this->denyAccessUnlessGranted(Access::PERM_ACCESS_DEBUG);

        $table = $this->createAdminTable('user_grants');
        $table->addHeader($this->t("Grants"), 'basic');

        foreach (node_access_grants('view', $account) as $realm => $gids) {
            $table->addRow($realm, implode(', ', $gids));
        }

        return $table->render();
    }

    /**
     * Acquire node grants, without saving them.
     */
    private function nodeAcquireGrants(NodeInterface $node)
    {
        $grants = module_invoke_all('node_access_records', $node);
        // Let modules alter the grants.
        drupal_alter('node_access_records', $grants, $node);
        // If no grants are set and the node is published, then use the default grant.
        if (empty($grants) && !empty($node->status)) {
            $grants[] = ['realm' => 'all', 'gid' => 0, 'grant_view' => 1, 'grant_update' => 0, 'grant_delete' => 0];
        } else {
            // Retain grants by highest priority.
            $grant_by_priority = [];
            foreach ($grants as $g) {
              $grant_by_priority[intval($g['priority'])][] = $g;
            }
            krsort($grant_by_priority);
            $grants = array_shift($grant_by_priority);
        }

        return $grants;
    }

    /**
     * Debug screen that displays node grants.
     */
    public function viewNodeGrantsAction(NodeInterface $node)
    {
        $this->denyAccessUnlessGranted(Access::PERM_ACCESS_DEBUG);

        $table = $this->createAdminTable('node_grants');
        $table->addHeader($this->t("Grants"), 'basic');

        foreach ($this->nodeAcquireGrants($node) as $grant) {
            $table->addRow(
                $grant['realm'] . ' / ' . $grant['gid'] . ' (' . $grant['priority'] . ')',
                implode(', ', [$grant['grant_view'], $grant['grant_update'], $grant['grant_delete']])
            );
        }

        return $table->render();
    }
}
