<?php

namespace MakinaCorpus\Ucms\Contrib\Controller;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Session\AccountInterface;

use MakinaCorpus\Drupal\Sf\Controller;
use MakinaCorpus\Ucms\Contrib\NodeCartDisplay;

use Symfony\Component\HttpFoundation\Request;

/**
 * Present the user a list of recent content
 */
class CartHistoryController extends Controller
{
    /**
     * @return AccountInterface
     */
    private function getCurrentUser()
    {
        return $this->get('current_user');
    }

    /**
     * @return EntityStorageInterface
     */
    private function getNodeStorage()
    {
        return $this->get('entity.manager')->getStorage('node');
    }

    /**
     * @return \DatabaseConnection
     */
    private function getDatabase()
    {
        return $this->get('database');
    }

    /**
     * Render the cart
     */
    private function renderCart(Request $request, $userId, $nodes, $cssId)
    {
        $display = (new NodeCartDisplay())
            ->setParameterName('cd')
            ->prepareFromQuery(
                $request->query->all()
            )
        ;

        $ret = [
            '#theme'    => 'ucms_contrib_cart',
            '#css_id'   => $cssId,
            '#account'  => $userId,
            '#items'    => $display->render($nodes),
        ];

        $ret['#attached']['library'][] = ['system', 'ui.droppable'];
        $ret['#attached']['library'][] = ['system', 'ui.draggable'];
        $ret['#attached']['library'][] = ['system', 'ui.sortable'];
        $ret['#attached']['library'][] = ['ucms_contrib', 'ucms_contrib'];

        return $ret;
    }

    /**
     * User node creation/update history
     */
    public function userUpdateHistoryAction(Request $request, $userId = null)
    {
        if (!$userId) {
            $userId = $this->getCurrentUser()->id();
        }

        $nodeIdList = $this
            ->getDatabase()
            ->select('node', 'n')
            ->fields('n', ['nid'])
            ->condition('n.uid', $userId)
            ->orderBy('n.changed', 'desc')
            ->orderBy('n.created', 'desc')
            ->range(0, 12)
            ->addTag('node_access')
            ->execute()
            ->fetchCol()
        ;

        if ($nodeIdList) {
            $nodes = $this->getNodeStorage()->loadMultiple($nodeIdList);
        } else {
            $nodes = [];
        }

        return $this->renderCart($request, $userId, $nodes, 'ucms-cart-recent-history');
    }

    /**
     * User browse history action
     */
    public function userReadHistoryAction(Request $request, $userId = null)
    {
        if (!$userId) {
            $userId = $this->getCurrentUser()->id();
        }

        $query = $this->getDatabase()->select('history', 'h');
        $query->join('node', 'n', "h.nid = n.nid");
        $nodeIdList = $query
            ->fields('h', ['nid'])
            ->condition('h.uid', $userId)
            ->orderBy('h.timestamp', 'desc')
            ->range(0, 12)
            ->addTag('node_access')
            ->execute()
            ->fetchCol()
        ;

        if ($nodeIdList) {
            $nodes = $this->getNodeStorage()->loadMultiple($nodeIdList);
        } else {
            $nodes = [];
        }

        return $this->renderCart($request, $userId, $nodes, 'ucms-cart-browse-history');
    }
}
