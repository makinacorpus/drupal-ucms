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
        $manager  = $this->getSiteManager();
        $origin   = null;
        $sites    = null;

        if ($node->site_id) {
            $origin = $manager->getStorage()->findOne($node->site_id);
        }
        if ($node->ucms_sites) {
            $sites = $manager->getStorage()->loadAll($node->ucms_sites);
        }

        return $this->render('module:ucms_site:Resources/views/NodeInfo/siteList.html.twig', [
            'origin'  => $origin,
            'allowed' => $node->ucms_allowed_sites,
            'sites'   => $sites,
            'node'    => $node,
        ]);
    }
}
