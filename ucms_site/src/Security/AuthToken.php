<?php

namespace MakinaCorpus\Ucms\Site\Security;

use MakinaCorpus\Ucms\Site\Structure\DateHolderTrait;

class AuthToken
{
    use DateHolderTrait;

    private $expired = null;
    private $site_id;
    private $token;
    private $ts_touched;
    private $ttl;
    private $user_id;

    public static function createNullInstance(int $siteId, int $userId = null): self
    {
        $instance = new self();
        $instance->site_id = $siteId;
        $instance->user_id = $userId;
        $instance->expired = true;

        return $instance;
    }

    public function getSiteId(): int
    {
        return (int)$this->site_id;
    }

    public function getUserId(): int
    {
        return (int)$this->user_id;
    }

    public function getTtl(): int
    {
        return (int)$this->tll;
    }

    public function touchedAt(): \DateTimeInterface
    {
        return $this->ensureDate($this->ts_touched ?? 0);
    }

    public function isExpired(): bool
    {
        if (null === $this->expired) {
            $this->expired = $this->touchedAt() < new \DateTime(\sprintf("now -%d seconds", $this->ttl));
        }

        return $this->expired;
    }

    public function isValid(string $token): bool
    {
        return $token && $this->user_id && $this->token === $token && !$this->isExpired();
    }
}
