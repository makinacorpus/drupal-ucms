<?php

namespace MakinaCorpus\Ucms\Site;

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
    const PERM_CONTENT_MANAGE_GROUP = 'content manage group';

    /**
     * Star content
     */
    const PERM_CONTENT_MANAGE_STARRED = 'content manage starred';

    /**
     * Flag content
     */
    const PERM_CONTENT_FLAG = 'content flag';

    /**
     * Flag content
     */
    const PERM_CONTENT_UNFLAG = 'content unflag';

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
    const PERM_CONTENT_VIEW_GROUP = 'content view group';

    /**
     * View published other sites content
     */
    const PERM_CONTENT_VIEW_OTHER = 'content view other';

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
    const PERM_SITE_MANGE_HOSTNAME = 'site manage hostname';

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
     * Import bulk media
     * View all users
     */
    const PERM_CONTENT_MEDIA_BULK_IMPORT = 'content media bulk import';

    /**
     * View all users
     */
    const PERM_USER_VIEW_ALL = 'users view all';

    /**
     * Manage all users
     */
    const PERM_USER_MANAGE_ALL = 'users manage all';

    /**
     * Allow to set or unset any role on any user.
     */
    const PERM_MANAGE_ALL_ROLES = 'users manage all roles';

    /**
     * Do not matter about context, you are the king.
     */
    const PERM_USER_GOD = 'users god';

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
}
