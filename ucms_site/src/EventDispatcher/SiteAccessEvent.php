<?php

namespace MakinaCorpus\Ucms\Site\EventDispatcher;

use Drupal\Core\Session\AccountInterface;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\Site;

class SiteAccessEvent extends SiteEvent
{
    private $denied = 0;
    private $granted = 0;

    /**
     * Default constructor
     */
    public function __construct(Site $site, AccountInterface $account, string $operation = Access::OP_VIEW, array $arguments = [])
    {
        $arguments['account'] = $account;
        $arguments['op'] = $operation;

        parent::__construct($site, $account->id(), $arguments);
    }

    /**
     * Get operation
     */
    public function getOperation(): string
    {
        return $this->arguments['op'] ?? '';
    }

    /**
     * Get user account
     */
    public function getUserAccount(): AccountInterface
    {
        return $this->arguments['account'];
    }

    /**
     * Deny (this is definitive).
     */
    public function deny()
    {
        ++$this->denied;
    }

    /**
     * Grant user (if someone explicitely denied, it will remain denied).
     */
    public function allow()
    {
        ++$this->granted;
    }

    /**
     * User is allowed by listeners
     */
    public function isGranted(): bool
    {
        // @todo for now this is a deny only event, in 2.x everything will be implemented using this
        return !$this->denied  /* && $this->granted */;
    }
}
