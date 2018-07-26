<?php

namespace MakinaCorpus\Ucms\Site;

use MakinaCorpus\Ucms\Site\Structure\PartialUserInterface;
use MakinaCorpus\Ucms\Site\Structure\PartialUserTrait;

/**
 * Single access record for a site and user couple
 *
 * This object is immutable
 *
 * @todo
 *   - reference the site object within
 *   - make the object extensible (global permissions within groups)
 */
class SiteAccessRecord implements PartialUserInterface
{
    use PartialUserTrait;

    /**
     * @var int
     */
    private $uid;

    /**
     * @var int
     */
    private $site_id;

    /**
     * @var int
     */
    private $site_state;

    /**
     * @var int
     */
    private $role;

    /**
     * {@inheritdoc}
     */
    public function getUserId(): int
    {
        return (int)$this->uid;
    }

    /**
     * Get site identifier
     */
    public function getSiteId(): int
    {
        return (int)$this->site_id;
    }

    /**
     * Denormalized site state value
     */
    public function getSiteState(): int
    {
        return (int)$this->site_state;
    }

    /**
     * Get role
     *
     * @return int
     *   One of the Access:ROLE_* constant
     */
    public function getRole(): int
    {
        return (int)$this->role;
    }
}
