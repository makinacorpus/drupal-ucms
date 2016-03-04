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
    const OP_UPDATE = 'update';

    /**
     * Delete operation
     */
    const OP_DELETE = 'delete';

    /**
     * Manage global content
     */
    const PERM_CONTENT_MANAGE_GLOBAL = 'content manage global';

    /**
     * Manage group content
     */
    const PERM_CONTENT_MANAGE_GROUP = 'content manage group';

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
     * Access the site dashboard
     */
    const PERM_SITE_DASHBOARD_ACCESS = 'site dashboard access';

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
}
