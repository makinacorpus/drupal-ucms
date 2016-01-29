<?php

namespace MakinaCorpus\Ucms\Site;

/**
 * Access constants
 */
final class Access
{
    /**
     * Default group identifier for grants where it does not make sense
     */
    const GID_DEFAULT = 1;

    /**
     * Default priority for grants
     */
    const PRIORITY_DEFAULT = 1;

    /**
     * Default realm for access checks
     */
    const REALM_PUBLIC = 'ucms_public';

    /**
     * Default realm for access checks
     */
    const REALM_SITE = 'ucms_site';

    /**
     * Default realm for access checks
     */
    const REALM_GLOBAL = 'ucms_global';

    /**
     * Default realm for access checks
     */
    const REALM_GLOBAL_LABELED = 'ucms_global_labeled';

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
     * Access site dashboard permission
     */
    const PERM_SITE_DASHBOARD_ACCESS = 'site dashboard access';

    /**
     * Request new site permission
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
     * User can view global labeled content permission
     */
    const PERM_GLOBAL_LABELED_VIEW = 'site content labeled view';

    /**
     * User can view global content permission
     */
    const PERM_GLOBAL_VIEW = 'site content global view';

    /**
     * User can edit global labeled content permission
     */
    const PERM_GLOBAL_LABELED_EDIT = 'site content labeled edit';

    /**
     * User can edit global content permission
     */
    const PERM_GLOBAL_EDIT = 'site content global edit';

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
