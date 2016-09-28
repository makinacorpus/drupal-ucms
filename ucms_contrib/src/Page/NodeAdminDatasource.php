<?php

namespace MakinaCorpus\Ucms\Contrib\Page;

use MakinaCorpus\Ucms\Contrib\Page\DefaultNodeDatasource;

final class NodeAdminDatasource extends DefaultNodeDatasource
{
    /**
     * {@inheritdoc}
     */
    protected function createTermFacets()
    {
        $ret = parent::createTermFacets();

        $entityManager = $this->getEntityManager();

        $ret[] = $this
            ->getSearch()
            ->createFacet('owner', null)
            ->setChoicesCallback(function ($values) use ($entityManager) {
                if ($accounts = $entityManager->getStorage('user')->loadMultiple($values)) {
                    foreach ($accounts as $index => $account) {
                        $accounts[$index] = filter_xss(format_username($account));
                    }
                    return $accounts;
                }
            })
            ->setTitle($this->t("Owner"))
        ;

        $siteManager = $this->getSiteManager();
        $currentUser = $this->getCurrentUser();

        if (!$siteManager->hasContext()) {
            $sites = [];
            foreach ($siteManager->loadOwnSites($currentUser) as $site) {
                $sites[$site->getId()] = check_plain($site->title);
            }

            $ret[] = $this
                ->getSearch()
                ->createFacet('site_id', null)
                ->setChoicesMap($sites)
                ->setExclusive(true)
                ->setTitle($this->t("My sites"))
            ;
        }

        return $ret;
    }
}
