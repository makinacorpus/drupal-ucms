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
     * @param int $node_id
     *   The node identifier to redirect to.
     * @param int|null $site_id
     *   (optional) The site the redirect belongs to.
     * @param int|null $id
     *   (optional) Unique redirect identifier for updates.
     */
    public function save($path, $node_id, $site_id = null, $id = null);

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
     * @param int $node_id
     *   The node identifier to redirect to.
     * @param int|null $site_id
     *   (optional) The site the redirect belongs to.
     *
     * @return bool
     *   TRUE if alias already exists and FALSE otherwise.
     */
    public function redirectExists($path, $node_id, $site_id = null);

    /**
     * Check if any redirect exists starting with $initial_substring.
     *
     * @param string $path
     *   Initial path substring to test against.
     * @param int|null $site_id
     *   (optional) The site the redirect belongs to.
     *
     * @return bool
     *   TRUE if any alias exists, FALSE otherwise.
     */
    public function pathHasMatchingRedirect($path, $site_id = null);
}
