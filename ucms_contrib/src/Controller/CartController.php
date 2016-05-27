<?php

namespace MakinaCorpus\Ucms\Contrib\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

use MakinaCorpus\Drupal\Sf\Controller;
use MakinaCorpus\Ucms\Contrib\CartStorage;
use MakinaCorpus\Ucms\Contrib\NodeCartDisplay;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class CartController extends Controller
{
    /**
     * @return AccountInterface
     */
    private function getCurrentUser()
    {
        return $this->get('current_user');
    }

    /**
     * @return CartStorage
     */
    private function getCartStorage()
    {
        return $this->get('ucms_contrib.cart');
    }

    /**
     * @return EntityStorageInterface
     */
    private function getNodeStorage()
    {
        return $this->get('entity.manager')->getStorage('node');
    }

    public function addAction(Request $request, NodeInterface $node, $mode = 'normal')
    {
        $userId = $this->getCurrentUser()->id();
        $cart   = $this->getCartStorage();

        if ($cart->addFor($userId, $node->nid)) {
            $node_view = node_view($node, UCMS_VIEW_MODE_FAVORITE);
            $status = 200;
            $ret = [
                'success' => true,
                'nid'     => $node->id(),
                'output'  => drupal_render($node_view),
            ];
        } else {
            $ret = ['error' => t("%title is already a favorite", ['%title' => $node->getTitle()])];
            $status = 400;
        }

        switch ($mode) {

            case 'nojs':
                return $this->redirectToRoute($request->query->get('destination'));

            case 'ajax':
                return (new AjaxResponse())
                    ->addCommand([
                        'command'   => 'invoke',
                        'selector'  => null,
                        'method'    => 'UcmsCartAdd',
                        'arguments' => [$ret],
                    ])
                ;

            default:
                return new JsonResponse($ret, $status);
        }
    }

    public function removeAction(Request $request, NodeInterface $node, $mode = null)
    {
        $userId = $this->getCurrentUser()->id();
        $cart   = $this->getCartStorage();

        $cart->removeFor($userId, $node->nid);

        switch ($mode) {

            case 'nojs':
                return $this->redirectToRoute($request->query->get('destination'));

            case 'ajax':
                return (new AjaxResponse())
                    ->addCommand([
                        'command'   => 'invoke',
                        'selector'  => null,
                        'method'    => 'UcmsCartRemove',
                        'arguments' => [['nid' => $node->id()]],
                    ])
                ;

            default:
                return new JsonResponse(['success' => true], 200);
        }
    }

    public function refreshAction(Request $request, $mode = null)
    {
        $content = $this->renderAction($request, $this->getCurrentUser()->id());
        $content = drupal_render($content);

        switch ($mode) {

            case 'nojs':
                return $this->redirectToRoute($request->query->get('destination', $request->getPathInfo()));

            case 'ajax':
                return (new AjaxResponse())
                    ->addCommand([
                        'command'   => 'invoke',
                        'selector'  => null,
                        'method'    => 'UcmsCartRefresh',
                        'arguments' => [$content],
                    ])
                ;

            default:
                return new JsonResponse(['content' => $content, 'success' => true], 200);
        }
    }

    public function renderAction(Request $request, $userId)
    {
        $nidList  = $this->getCartStorage()->listFor($userId);
        $nodes    = $nidList ? node_load_multiple($nidList) : [];

        $display = (new NodeCartDisplay())
            ->setParameterName('cd')
            ->prepareFromQuery(
                $request->query->all()
            )
        ;

        $ret = [
            '#theme'    => 'ucms_contrib_cart',
            '#account'  => $userId,
            '#display'  => $display->renderLinks($request->getPathInfo()),
            '#items'    => $display->render($nodes),
        ];

        $ret['#attached']['library'][] = ['system', 'ui.droppable'];
        $ret['#attached']['library'][] = ['system', 'ui.draggable'];
        $ret['#attached']['library'][] = ['system', 'ui.sortable'];
        $ret['#attached']['library'][] = ['ucms_contrib', 'ucms_contrib'];

        return $ret;
    }
}
