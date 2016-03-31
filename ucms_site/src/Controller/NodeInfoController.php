<?php

namespace MakinaCorpus\Ucms\Site\Controller;

use Drupal\node\NodeInterface;

use MakinaCorpus\Drupal\Sf\Controller;
use MakinaCorpus\Ucms\Site\SiteManager;

class NodeInfoController extends Controller
{
    /**
     * @return SiteManager
     */
    private function getSiteManager()
    {
        return $this->get('ucms_site.manager');
    }

    public function siteListAction(NodeInterface $node)
    {
        $manager = $this->getSiteManager();

        return $this->render('module:ucms_site:Resources/views/NodeInfo/siteList.html.twig', [
            'sites' => $manager->getStorage()->loadAll($node->ucms_sites),
        ]);
    }
}
