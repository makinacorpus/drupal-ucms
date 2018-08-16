<?php

namespace MakinaCorpus\Ucms\Site\Security;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Database\Connection;

/**
 * @todo unit test me
 */
class AuthTokenStorage
{
    const TOKEN_SIZE = 32;

    private $database;

    /**
     * Default constructor
     */
    public function __construct(Connection $database)
    {
        $this->database = $database;
    }

    /**
     * Load authentication token for conditions
     */
    public function load(int $siteId, int $userId): AuthToken
    {
        $result = $this
            ->database
            ->query(
                "SELECT * FROM {ucms_auth_token} WHERE site_id = ? AND user_id = ?",
                [$siteId, $userId]
            )
        ;
        $result->setFetchMode(\PDO::FETCH_CLASS, AuthToken::class);
        $authToken = $result->fetch();

        if (!$authToken) {
            $authToken = AuthToken::createNullInstance($siteId, $userId);
        }

        return $authToken;
    }

    /**
     * Find
     */
    public function find(int $siteId, string $token): AuthToken
    {
        $result = $this
            ->database
            ->query(
                "SELECT * FROM {ucms_auth_token} WHERE site_id = ? AND token = ?",
                [$siteId, $token]
            )
        ;
        $result->setFetchMode(\PDO::FETCH_CLASS, AuthToken::class);
        $authToken = $result->fetch();

        if (!$authToken) {
            $authToken = AuthToken::createNullInstance($siteId);
        }

        return $authToken;
    }

    public function create(int $siteId, int $userId): AuthToken
    {
        return $this->merge($this->load($siteId, $userId), Crypt::randomBytesBase64(self::TOKEN_SIZE));
    }

    /**
     * Merge authentication token and return the new one
     */
    public function merge(AuthToken $authToken, string $token): AuthToken
    {
        $this
            ->database
            ->merge('ucms_auth_token')
            ->keys([
                'site_id' => $siteId = $authToken->getSiteId(),
                'user_id' => $userId = $authToken->getUserId(),
            ])
            ->fields([
                'token' => $token,
                'ts_touched' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                'ttl' => $authToken->getTtl(),
            ])
        ;

        // RETURNING clause would have been perfect here
        return $this->load($siteId, $userId);
    }

    /**
     * Delete single
     */
    public function delete(int $siteId, int $userId)
    {
        $this
            ->database
            ->query(
                "DELETE FROM {ucms_auth_token} WHERE site_id = ? AND user_id = ?",
                [$siteId, $userId]
            )
        ;
    }

    /**
     * Delete all for site
     */
    public function deleteForSite(int $siteId)
    {
        $this
            ->database
            ->query(
                "DELETE FROM {ucms_auth_token} WHERE site_id = ? AND user_id = ?",
                [$siteId]
            )
        ;
    }

    /**
     * Delete all for user
     */
    public function deleteForUser(int $userId)
    {
        $this
            ->database
            ->query(
                "DELETE FROM {ucms_auth_token} WHERE site_id = ? AND user_id = ?",
                [$userId]
            )
        ;
    }

    public function deleteExpired()
    {
        throw new \Exception("Not implemented yet");
        $this->database->query("DELETE FROM {ucms_auth_token} WHERE  ");
    }
}
