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
     *   (optional) Unique redirect identifier.
     *
     * @return array|false
     *   FALSE if the redirect could not be saved or an associative array containing
     *   the following keys:
     *   - path (string): The redirect path with a starting slash.
     *   - nid (int): The node identifier.
     *   - site_id (int): The site identifier.
     *   - id (int): Unique path alias identifier.
     */
    public function save($path, $node_id, $site_id = null, $id = null);

    /**
     * Fetches a specific redirect from the database.
     *
     * @param array $conditions
     *   An array of query conditions.
     *
     * @return array|false
     *   FALSE if no alias was found or an associative array containing the
     *   following keys:
     *   - path (string): The redirect path with a starting slash.
     *   - nid (int): The node identifier.
     *   - site_id (int): The site identifier.
     *   - id (int): Unique path alias identifier.
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
     * Loads redirects for admin listing.
     *
     * @param array $header
     *   Table header.
     * @param string|null $keys
     *   (optional) Search keyword that may include one or more '*' as wildcard
     *   values.
     *
     * @return array
     *   Array of items to be displayed on the current page.
     */
    public function getAliasesForAdminListing($header, $keys = null);

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
