<?php

namespace MakinaCorpus\Ucms\Site\EventDispatcher;

use MakinaCorpus\Ucms\Site\Site;

class SiteCloneEvent extends SiteEvent
{
    private $template;

    /**
     * Default constructor
     */
    public function __construct(Site $site, Site $template, $userId = null, array $arguments = [])
    {
        $arguments['uid'] = $userId;

        parent::__construct($site, $userId, $arguments);

        $this->template = $template;
    }

    /**
     * Get site
     */
    public function getTemplateSite(): Site
    {
        return $this->template;
    }
}
