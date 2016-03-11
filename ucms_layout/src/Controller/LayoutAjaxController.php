<?php

namespace MakinaCorpus\Ucms\Layout\Controller;

use Drupal\Core\Entity\EntityStorageInterface;

use MakinaCorpus\Drupal\Sf\Controller;
use MakinaCorpus\Ucms\Layout\ContextManager;
use MakinaCorpus\Ucms\Layout\Item;
use MakinaCorpus\Ucms\Layout\Layout;
use MakinaCorpus\Ucms\Site\Site;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class LayoutAjaxController extends Controller
{
    private function validateInputAndGetRegionName(Request $request)
    {
        if (!$request->isMethod('POST')) {
            throw $this->createNotFoundException();
        }
        if (!$request->isXmlHttpRequest()) {
            throw $this->createNotFoundException();
        }

        // CSRF protection.
        $token = $request->get(ContextManager::PARAM_AJAX_TOKEN);
        if (empty($token)) {
            throw $this->createAccessDeniedException(); // No CSRF token.
        }
        if (!drupal_valid_token($token)) {
            throw $this->createAccessDeniedException(); // Invalid CSRF token.
        }

        // Input validation.
        $region = $request->get('region');
        if (!$region) {
            throw $this->createNotFoundException();
        }

        return $region;
    }

    /**
     * @return EntityStorageInterface
     */
    private function getNodeStorage()
    {
        return $this->get('entity.manager')->getStorage('node');
    }

    /**
     * @return ContextManager
     */
    private function getContextManager()
    {
        return $this->get('ucms_layout.context_manager');
    }

    /**
     * @return Site
     */
    private function getSiteContext()
    {
        $site = $this->get('ucms_site.manager')->getContext();

        if (!$site) {
            throw $this->createNotFoundException();
        }

        return $site;
    }

    /**
     * Add item to a a region callback
     */
    public function addItemAction(Request $request, Layout $layout)
    {
        $region = $this->validateInputAndGetRegionName($request);

        $nid = $request->get('nid');
        if (empty($nid)) {
            throw $this->createNotFoundException();
        }
        if (!$node = $this->getNodeStorage()->load($nid)) {
            throw $this->createNotFoundException();
        }
        if (!$node->access('view')) {
            throw $this->createAccessDeniedException();
        }

        $viewmode = $request->get('view_mode', 'teaser');
        $position = $request->get('position');
        $manager  = $this->getContextManager();
        $site     = $this->getSiteContext();
        $item     = new Item($nid, $viewmode);

        $layout->getRegion($region)->addAt($item, $position);

        if ($manager->isPageContextRegion($region, $site->theme)) {
            $manager->getPageContext()->getStorage()->save($layout);
        } else if ($manager->isTransversalContextRegion($region, $site->theme)) {
            $manager->getSiteContext()->getStorage()->save($layout);
        } else {
            throw $this->createAccessDeniedException();
        }

        $renderedNode = node_view($node, $viewmode);

        return new JsonResponse(['success' => true, 'node' => drupal_render($renderedNode)]);
    }

    /**
     * Add item to a a region callback
     */
    public function removeItemAction(Request $request, Layout $layout)
    {
        $region   = $this->validateInputAndGetRegionName($request);
        $position = $request->get('position', 0);
        $manager  = $this->getContextManager();
        $site     = $this->getSiteContext();

        $layout->getRegion($region)->removeAt($position);

        if ($manager->isPageContextRegion($region, $site->theme)) {
            $manager->getPageContext()->getStorage()->save($layout);
        } else if ($manager->isTransversalContextRegion($region, $site->theme)) {
            $manager->getSiteContext()->getStorage()->save($layout);
        } else {
            throw $this->createAccessDeniedException();
        }

        return new JsonResponse(['success' => true]);
    }

    /**
     * Add item to a a region callback
     */
    public function moveItemAction(Request $request, Layout $layout)
    {
        $region = $this->validateInputAndGetRegionName($request);

        $nid = $request->get('nid');
        if (empty($nid)) {
            throw $this->createNotFoundException();
        }
        if (!$node = $this->getNodeStorage()->load($nid)) {
            throw $this->createNotFoundException();
        }
        if (!$node->access('view')) {
            throw $this->createAccessDeniedException();
        }

        $viewmode = $request->get('view_mode', 'teaser');
        $manager  = $this->getContextManager();
        $site     = $this->getSiteContext();
        $item     = new Item($nid, $viewmode);
        $position = $request->get('position');

        $prevRegion     = $request->get('prevRegion');
        $prevPosition   = $request->get('prevPosition');

        if (!empty($prevRegion)) {
            $layout->getRegion($prevRegion)->removeAt($prevPosition);
        } else {
            $layout->getRegion($region)->removeAt($prevPosition);
        }

        $layout->getRegion($region)->addAt($item, $position);

        if ($manager->isPageContextRegion($region, $site->theme)) {
            $manager->getPageContext()->getStorage()->save($layout);
        } else if ($manager->isTransversalContextRegion($region, $site->theme)) {
            $manager->getSiteContext()->getStorage()->save($layout);
        } else {
            throw $this->createAccessDeniedException();
        }

        $renderedNode = node_view($node, $viewmode);

        return new JsonResponse(['success' => true, 'node' => drupal_render($renderedNode)]);
    }
}