<?php

namespace MakinaCorpus\Ucms\Seo\Path;

/**
 * Redirect storage interface.
 */
interface RedirectStorageInterface
{
    /**
     * Saves a redirect to the database.
     *
     * @param string $path
     *   The redirect path to redirect from.
     * @param int $nodeId
     *   The node identifier to redirect to.
     * @param int|null $siteId
     *   (optional) The site the redirect belongs to.
     * @param int|null $id
     *   (optional) Unique redirect identifier for updates.
     */
    public function save(string $path, int $nodeId, int $siteId = null, int $id = null);

    /**
     * Fetches a specific redirect from the database.
     *
     * @param array $conditions
     *   An array of query conditions.
     *
     * @return null|Redirect
     */
    public function load($conditions);

    /**
     * Deletes a redirect.
     *
     * @param array $conditions
     *   An array of criteria.
     */
    public function delete($conditions);

    /**
     * Checks if redirect already exists.
     *
     * @param string $path
     *   The redirect path to redirect from.
     * @param int $nodeId
     *   The node identifier to redirect to.
     * @param int|null $siteId
     *   (optional) The site the redirect belongs to.
     *
     * @return bool
     *   TRUE if alias already exists and FALSE otherwise.
     */
    public function redirectExists(string $path, int $nodeId, int $siteId = null) : bool;

    /**
     * Check if any redirect exists starting with $initial_substring.
     *
     * @param string $path
     *   Initial path substring to test against.
     * @param int|null $siteId
     *   (optional) The site the redirect belongs to.
     *
     * @return bool
     *   TRUE if any alias exists, FALSE otherwise.
     */
    public function pathHasMatchingRedirect(string $path, int $siteId = null) : bool;
}
