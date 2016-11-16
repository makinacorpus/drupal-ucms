<?php

namespace MakinaCorpus\Ucms\Site;

/**
 * Access constants
 */
final class Access
{
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
     * Grants for group content
     */
    const PROFILE_GROUP_READONLY = 'content_group_ro';

    /**
     * Grants for group content
     */
    const PROFILE_GROUP = 'content_group_editor';

    /**
     * Users that can manage all sites
     */
    const PROFILE_SITE_ADMIN = 'site_admin_editor';

    /**
     * Users that can see all sites
     */
    const PROFILE_SITE_ADMIN_RO = 'site_admin_ro';

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
     * ACL permission for site: manage webmasters
     */
    const ACL_PERM_CONTENT_PROMOTE_GROUP = 'promote to group';

    /**
     * ACL permission for site: manage webmasters
     */
    const ACL_PERM_SITE_MANAGE_USERS = 'manage users';

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
     * Explicitely tell that the query should not filter out nodes that don't
     * elong to the current site context. This site filtering is only done
     * whenever the query is tagged with 'node_access'.
     */
    const QUERY_TAG_CONTEXT_OPT_OUT = 'ucms_site_access_opt_out';
}
