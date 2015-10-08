<?php

namespace Ucms\Layout;

trait TokenAwareTrait
{
    /**
     * @var string
     */
    private $token;

    /**
     * Set current page session token
     *
     * This identifiers is unique for each page being edited, the same way
     * form tokens are, and allow us to change on the fly the unique cache
     * identifier ensuring no conflicts.
     *
     * @param string $token
     *
     * @return \Ucms\Layout\TemporaryStorage
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Get current page session token
     *
     * @return string
     */
    public function getToken()
    {
        if (!$this->token) {
            throw new \LogicException("Token is not set");
        }

        return $this->token;
    }

    /**
     * Does this instance has a token
     *
     * @return boolean
     */
    public function hasToken()
    {
        return !empty($this->token);
    }
}
