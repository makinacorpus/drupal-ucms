<?php


namespace MakinaCorpus\Ucms\Extranet\EventDispatcher;

use Drupal\Core\Session\AccountInterface;

use MakinaCorpus\Ucms\Site\Site;

use Symfony\Component\EventDispatcher\GenericEvent;


class ExtranetMemberEvent extends GenericEvent
{
    const EVENT_REGISTER  = 'extranet:member_register';
    const EVENT_ACCEPT    = 'extranet:member_accept';
    const EVENT_REJECT    = 'extranet:member_reject';


    /**
     * @var Site
     */
    private $site;


    /**
     * Constructor.
     *
     * @param AccountInterface $member
     * @param Site $site
     * @param array $arguments
     */
    public function __construct(AccountInterface $member, Site $site, array $arguments = [])
    {
        $this->site = $site;
        $arguments['site_id'] = $site->getId();

        if (\Drupal::currentUser()->isAuthenticated()) {
            $arguments['uid'] = \Drupal::currentUser()->id();
        }

        parent::__construct($member, $arguments);
    }


    /**
     * Get the extranet site concerned by the registration.
     *
     * @return Site
     */
    public function getSite()
    {
        return $this->site;
    }
}


