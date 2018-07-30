<?php

namespace MakinaCorpus\Ucms\Site;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Access constants
 */
final class Access
{
    /**
     * View operation
     */
    const OP_VIEW = 'view';

    /**
     * Update operation
     */
    const OP_CREATE = 'create';

    /**
     * Update operation
     */
    const OP_UPDATE = 'update';

    /**
     * Delete operation
     */
    const OP_DELETE = 'delete';

        /**
     * Grants for anonymous users
     */
    const PROFILE_PUBLIC = 'ucms_public';

    /**
     * This is for technical administrators only
     */
    const PROFILE_GOD = 'god_mode';

    /**
     * Grants for local webmasters
     */
    const PROFILE_SITE_WEBMASTER = 'site_webmaster';

    /**
     * Grants for local webmasters
     */
    const PROFILE_OWNER = 'owner';

    /**
     * Grants for site members that cannot edit content
     */
    const PROFILE_SITE_READONLY = 'site_ro';

    /**
     * Grants for local contributors
     */
    const PROFILE_READONLY = 'site_all_ro';

    /**
     * Grants for other sites
     */
    const PROFILE_OTHER = 'other';

    /**
     * Grants for people accessing the dashboard
     */
    const PROFILE_GLOBAL_READONLY = 'content_global_ro';

    /**
     * Grants for global content
     */
    const PROFILE_GLOBAL = 'content_global_editor';

    /**
     * Grants for institutional content
     */
    const PROFILE_CORPORATE_READER = 'corporate_reader';

    /**
     * Grants for institutional content
     */
    const PROFILE_CORPORATE_ADMIN = 'corporate_admin';

    /**
     * Users that can manage all sites
     */
    const PROFILE_SITE_ADMIN = 'site_admin_editor';

    /**
     * Users that can manage all sites
     */
    const PROFILE_SITE_GOD = 'site_admin_god';

    /**
     * Users that can see all sites
     */
    const PROFILE_SITE_ADMIN_RO = 'site_admin_ro';

    /**
     * Platform wide group mananger
     */
    const PROFILE_GROUP_GOD = 'group_god';

    /**
     * Group administrators
     */
    const PROFILE_GROUP_ADMIN = 'group_admin';

    /**
     * Group members
     */
    const PROFILE_GROUP_MEMBER = 'group_member';

    /**
     * Admins that can see orphaned content
     */
    const PROFILE_GROUP_ORPHAN_READER = 'ucms_group_orphan_reader';

    /**
     * Admins that can manage all users
     */
    const PROFILE_USER_GOD = 'user_admin_god';

    /**
     * Users that can see all other users
     */
    const PROFILE_USER_READER = 'user_reader';

    /**
     * Default group identifier for grants where it does not make sense
     */
    const ID_ALL = 0;

    /**
     * God mode for content, do anything with anything
     */
    const PERM_CONTENT_GOD = 'content god';

    /**
     * Manage global content
     */
    const PERM_CONTENT_MANAGE_GLOBAL = 'content manage global';

    /**
     * Manage group content
     */
    const PERM_CONTENT_MANAGE_CORPORATE = 'content manage group';

    /**
     * Transfer content ownership.
     */
    const PERM_CONTENT_TRANSFER_OWNERSHIP = 'content transfer ownership';

    /**
     * View all content in all circumstances
     */
    const PERM_CONTENT_VIEW_ALL = 'content view all';

    /**
     * View published global content
     */
    const PERM_CONTENT_VIEW_GLOBAL = 'content view global';

    /**
     * View published group content
     */
    const PERM_CONTENT_VIEW_CORPORATE = 'content view group';

    /**
     * View published other sites content
     */
    const PERM_CONTENT_VIEW_OTHER = 'content view other';

    /**
     * Access the group admin dashboard
     */
    const PERM_GROUP_DASHBOARD_ACCESS = 'group dashboard access';

    /**
     * Manage all groups (group god)
     */
    const PERM_GROUP_MANAGE_ALL = 'group manage all';

    /**
     * Manage orphaned content (outside of groups)
     */
    const PERM_GROUP_MANAGE_ORPHAN = 'group manage orphan';

    /**
     * Access the site dashboard
     */
    const PERM_SITE_DASHBOARD_ACCESS = 'site dashboard access';

    /**
     * God mode for site, do anything with anything
     */
    const PERM_SITE_GOD = 'site god';

    /**
     * Manage technical aspects of sites, such as hostnames
     */
    const PERM_SITE_MANAGE_HOSTNAME = 'site manage hostname';

    /**
     * Request a new site
     */
    const PERM_SITE_REQUEST = 'site request';

    /**
     * Manage all sites no matter the state permission
     */
    const PERM_SITE_MANAGE_ALL = 'site manage all';

    /**
     * View all sites no matter the state permission
     */
    const PERM_SITE_VIEW_ALL = 'site view all';

    /**
     * View all users
     */
    const PERM_USER_VIEW_ALL = 'users view all';

    /**
     * Manage all users
     */
    const PERM_USER_MANAGE_ALL = 'users manage all';

    /**
     * ACL permission for site: manage webmasters
     */
    const ACL_PERM_CONTENT_PROMOTE_CORPORATE = 'promote to corporate';

    /**
     * ACL permission for site: manage webmasters
     */
    const ACL_PERM_MANAGE_USERS = 'manage users';

    /**
     * ACL permission for site: manage webmasters
     */
    const ACL_PERM_MANAGE_SITES = 'manage sites';

    /**
     * ACL permission for site: manage webmasters
     */
    const ACL_PERM_SITE_EDIT_TREE = 'edit tree';

    /**
     * Site relative role: none
     */
    const ROLE_NONE = 0;

    /**
     * Site relative role: webmaster
     */
    const ROLE_WEBMASTER = 1;

    /**
     * Site relative role: contributor
     */
    const ROLE_CONTRIB = 2;

    /**
     * Group administrator
     */
    const ROLE_GROUP_ADMIN = 2;

    /**
     * Can admin a certain taxonomy vocabulary
     */
    const ROLE_TAXO_ADMIN = 1;

    /**
     * Can read a certain taxonomy vocabulary
     */
    const ROLE_TAXO_READER = 2;

    /**
     * Explicitely tell that the query should not filter out nodes that don't
     * elong to the current site context. This site filtering is only done
     * whenever the query is tagged with 'node_access'.
     */
    const QUERY_TAG_CONTEXT_OPT_OUT = 'ucms_site_access_opt_out';

    /**
     * Drupal did not enabled the Yaml::PARSE_CONSTANT flag on Symfony's YAML
     * component while parsing permission, so we use dynamic permission spawn
     * instead so we can use them as array keys.
     */
    public static function getPermissionArray(): array
    {
        return [
            self::PERM_CONTENT_GOD => [
                'title' => new TranslatableMarkup("Content god mode"),
                'description' => new TranslatableMarkup("This permission bypasses everything that can be bypassed, for technical administrators only"),
            ],
            self::PERM_CONTENT_MANAGE_GLOBAL => [
                'title' => new TranslatableMarkup("Manage global content"),
            ],
            self::PERM_CONTENT_MANAGE_CORPORATE => [
                'title' => new TranslatableMarkup("Manage group content"),
            ],
            self::PERM_CONTENT_TRANSFER_OWNERSHIP => [
                'title' => new TranslatableMarkup("Transfer content ownership to another user"),
            ],
            self::PERM_CONTENT_VIEW_ALL => [
                'title' => new TranslatableMarkup("View all content no matter where it stands"),
            ],
            self::PERM_CONTENT_VIEW_GLOBAL => [
                'title' => new TranslatableMarkup("View global published content"),
            ],
            self::PERM_CONTENT_VIEW_CORPORATE => [
                'title' => new TranslatableMarkup("View group published content"),
            ],
            self::PERM_CONTENT_VIEW_OTHER => [
                'title' => new TranslatableMarkup("View other site content"),
            ],
            self::PERM_SITE_MANAGE_HOSTNAME => [
                'title' => new TranslatableMarkup("Site change hostnames"),
                'description' => new TranslatableMarkup("This permission bypasses everything that can be bypassed, for technical administrators only"),
            ],
            self::PERM_SITE_GOD => [
                'title' => new TranslatableMarkup("Site god mode"),
                'description' => new TranslatableMarkup("This permission bypasses everything that can be bypassed, for technical administrators only"),
            ],
            self::PERM_SITE_REQUEST => [
                'title' => new TranslatableMarkup("Request new site"),
            ],
            self::PERM_SITE_DASHBOARD_ACCESS => [
                'title' => new TranslatableMarkup("Access to site dashboard"),
            ],
            self::PERM_SITE_MANAGE_ALL => [
                'title' => new TranslatableMarkup("Manage all sites no matter their state is"),
            ],
            self::PERM_SITE_VIEW_ALL => [
                'title' => new TranslatableMarkup("View all sites no matter their state is"),
            ],
            self::PERM_GROUP_DASHBOARD_ACCESS => [
                'title' => new TranslatableMarkup("Access to group dashboard"),
            ],
            self::PERM_GROUP_MANAGE_ALL => [
                'title' => new TranslatableMarkup("Manage all groups"),
            ],
            self::PERM_GROUP_MANAGE_ORPHAN => [
                'title' => new TranslatableMarkup("Manage orphan content"),
            ],
            self::PERM_USER_MANAGE_ALL => [
                'title' => new TranslatableMarkup("Manage all users"),
            ],
            self::PERM_USER_VIEW_ALL => [
                'title' => new TranslatableMarkup("View all users"),
            ],
        ];
    }
}
