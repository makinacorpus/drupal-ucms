<?php

namespace MakinaCorpus\Ucms\Site;

/**
 * Access constants.
 */
final class Access
{
    /**
     * Default realm for access checks.
     */
    const REALM_SITE = 'ucms_site';

    /**
     * Default realm for access checks.
     */
    const REALM_GLOBAL = 'ucms_global';

    /**
     * View operation.
     */
    const OP_VIEW = 'view';

    /**
     * Update operation.
     */
    const OP_UPDATE = 'update';

    /**
     * Delete operation.
     */
    const OP_DELETE = 'delete';

    /**
     * Internet visitor role.
     */
    const ROLE_ANONYMOUS = 'anonymous';

    /**
     * Global database webmaster.
     */
    const ROLE_WEBMASTER_GLOBAL = 'webmaster_global';

    /**
     * Local database webmaster.
     */
    const ROLE_WEBMASTER_LOCAL = 'webmaster_local';
}
