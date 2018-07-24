<?php

namespace MakinaCorpus\Ucms\Site\Page;

use Drupal\Core\Database\Connection;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Ucms\Dashboard\Page\AbstractDatasource;
use MakinaCorpus\Ucms\Dashboard\Page\LinksFilterDisplay;
use MakinaCorpus\Ucms\Dashboard\Page\PageState;
use MakinaCorpus\Ucms\Site\SiteManager;

class WebmasterAdminDatasource extends AbstractDatasource
{
    use StringTranslationTrait;

    private $db;
    private $manager;

    /**
     * Default constructor
     */
    public function __construct(Connection $db, SiteManager $manager)
    {
        $this->db = $db;
        $this->manager = $manager;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters($query)
    {
        if (empty($query['site_id'])) {
            return [];
        }

        $site = $this->manager->getStorage()->findOne($query['site_id']);
        $relativeRoles = $this->manager->getAccess()->collectRelativeRoles($site);

        $choices = [];
        foreach ($relativeRoles as $rrid => $label) {
          $choices[$rrid] = $label;
        }

        return [(new LinksFilterDisplay('role', $this->t("Role")))->setChoicesMap($choices)];
    }

    /**
     * {@inheritdoc}
     */
    public function getItems($query, PageState $pageState)
    {
        if (empty($query['site_id'])) {
            return [];
        }

        $site = $this->manager->getStorage()->findOne($query['site_id']);

        if (!empty($query['role'])) {
            $total = $this->manager->getAccess()->countUsersWithRole($site, $query['role']);
            $accessRecords = $this->manager->getAccess()->listUsersWithRole($site, $query['role'], $pageState->getLimit(), $pageState->getOffset());
        } else {
            $total = $this->manager->getAccess()->countUsersWithRole($site);
            $accessRecords = $this->manager->getAccess()->listUsersWithRole($site, null, $pageState->getLimit(), $pageState->getOffset());
        }

        $pageState->setTotalItemCount($total);

        return $accessRecords;
    }

    /**
     * {@inheritdoc}
     */
    public function hasSearchForm()
    {
        return false;
    }
 }
