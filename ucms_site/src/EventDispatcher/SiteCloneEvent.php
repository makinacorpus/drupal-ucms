<?php

namespace MakinaCorpus\Ucms\Site\EventDispatcher;

use MakinaCorpus\Ucms\Site\Site;

class SiteCloneEvent extends SiteEvent
{
    private $template;

    /**
     * Default constructor
     *
     * @param Site $site
     * @param Site $template
     * @param int $userId
     * @param array $arguments
     */
    public function __construct(Site $site, Site $template, $userId = null, array $arguments = [])
    {
        $arguments['uid'] = $userId;

        parent::__construct($site, $userId, $arguments);

        $this->template = $template;
    }

    /**
     * Get site
     *
     * @return Site
     */
    public function getTemplateSite()
    {
        return $this->template;
    }
}
