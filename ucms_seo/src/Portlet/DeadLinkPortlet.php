<?php

namespace MakinaCorpus\Ucms\Seo\Portlet;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\Page\DatasourceInterface;
use MakinaCorpus\Ucms\Dashboard\Page\DisplayInterface;
use MakinaCorpus\Ucms\Dashboard\Page\PageState;
use MakinaCorpus\Ucms\Dashboard\Portlet\AbstractAdminPortlet;

class DeadLinkPortlet extends AbstractAdminPortlet
{
    use StringTranslationTrait;

    private $display;

    public function __construct(DatasourceInterface $datasource, DisplayInterface $display)
    {
        parent::__construct($datasource);

        $this->display = $display;
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

    protected function getDisplay(&$query, PageState $pageState)
    {
        return $this->display;
    }

    public function userIsAllowed(AccountInterface $account)
    {
        return true;  // TODO - FIXME (don't know what to do)
    }
}
