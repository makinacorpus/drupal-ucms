<?php


namespace MakinaCorpus\Ucms\Seo\Portlet;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\Page\PageState;
use MakinaCorpus\Ucms\Dashboard\Portlet\AbstractAdminPortlet;


class DeadLinkPortlet extends AbstractAdminPortlet
{
    use StringTranslationTrait;
    
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
        return new DeadLinkPortletDisplay();
    }
    
    public function userIsAllowed(AccountInterface $account)
    {
        return true;  // TODO - FIXME (don't know what to do)
    }
}
