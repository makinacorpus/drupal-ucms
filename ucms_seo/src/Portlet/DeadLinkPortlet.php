<?php

namespace MakinaCorpus\Ucms\Seo\Portlet;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Drupal\Dashboard\Page\DatasourceInterface;
use MakinaCorpus\Drupal\Dashboard\Page\PageBuilder;
use MakinaCorpus\Drupal\Dashboard\Portlet\AbstractPortlet;

/**
 * Dead links detector portlet
 */
class DeadLinkPortlet extends AbstractPortlet
{
    use StringTranslationTrait;

    private $datasource;

    public function __construct(DatasourceInterface $datasource)
    {
        $this->datasource = $datasource;
    }

    public function getTitle()
    {
        return $this->t("Dead links");
    }

    public function getPath() {}

    public function getActions()
    {
        return [];
    }

    protected function createPage(PageBuilder $pageBuilder)
    {
        $pageBuilder
            ->setDatasource($this->datasource)
            ->setAllowedTemplates(['table' => 'module:ucms_seo:Portlet/page-deadlink.html.twig'])
        ;
    }

    public function userIsAllowed(AccountInterface $account)
    {
        return true;  // TODO - FIXME (don't know what to do)
    }
}
