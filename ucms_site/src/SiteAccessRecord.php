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
final class SiteAccessRecord implements PartialUserInterface
{
    use PartialUserTrait;

    private $uid;
    private $site_id;
    private $site_state;
    private $role;

    /**
     * Create partial instance, for internal use only
     */
    public static function createPartial($siteId, $userId)
    {
        $instance = new self();
        $instance->uid = $userId;
        $instance->site_id = $siteId;

        return $instance;
    }

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

    /**
     * Generate unique identifier
     */
    public function generateUniqueId(): string
    {
        return $this->site_id.'-'.$this->uid;
    }
}
