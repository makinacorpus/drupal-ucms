<?php

namespace MakinaCorpus\Ucms\Site\EventDispatcher;

/**
 * Site events
 */
final class SiteEvents
{
    // Persistence
    const EVENT_CREATE = 'site:create';
    const EVENT_DELETE = 'site:delete';
    const EVENT_PRECREATE = 'site:preCreate';
    const EVENT_PREDELETE = 'site:preDelete';
    const EVENT_PRESAVE = 'site:preSave';
    const EVENT_SAVE = 'site:save';

    // Stuff happens
    const EVENT_ATTACH = 'site:node.attach';
    const EVENT_DETACH = 'site:node.dettach';
    const EVENT_CLONE = 'site:clone';
    const EVENT_SWITCH = 'site:switch';

    // Admin screens events
    const SITE_DISPLAY_TABLE = 'ucms_site_table_display';

    // Site initialization
    const EVENT_INIT = 'site:init';

    // No site was inited, and we are currently in admin
    const EVENT_MASTER_INIT = 'site:master-init';

    // Site context is being dropped
    const EVENT_DROP = 'site:drop';

    // Site access is requested
    const EVENT_ACCESS = 'site:access';

    // Site contributors
    const EVENT_WEBMASTER_CREATE = 'site:webmaster_add_new';
    const EVENT_WEBMASTER_ATTACH = 'site:webmaster_add_existing';
    const EVENT_WEBMASTER_REMOVE = 'site:webmaster_delete';
    const EVENT_WEBMASTER_PROMOTE = 'site:webmaster_promote';
    const EVENT_WEBMASTER_DEMOTE = 'site:webmaster_demote';
    const EVENT_WEBMASTER_CHANGE_ROLE = 'site:webmaster_change_role';
}
