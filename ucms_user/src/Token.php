<?php


namespace MakinaCorpus\Ucms\User;


class Token
{
    /**
     * @var int
     */
    public $uid;

    /**
     * @var string
     */
    public $token;

    /**
     * @var \DateTime
     */
    public $expiration_date;


    /**
     * Is the token still valid?
     */
    public function isValid()
    {
        return ($this->expiration_date->getTimestamp() >= REQUEST_TIME);
    }


    /**
     * Is the token expired?
     */
    public function isExpired()
    {
        return ($this->expiration_date->getTimestamp() < REQUEST_TIME);
    }


    /**
     * Generates the token's key by using drupal_random_key()
     */
    public function generateKey($byte_count = 32)
    {
        $this->token = drupal_random_key($byte_count);

        if (strlen($this->token) > 128) {
            $this->token = substr($this->token, 0, 128);
        }

        return $this;
    }
}
