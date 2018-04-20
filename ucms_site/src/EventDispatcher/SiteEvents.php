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

    // Admin screens events
    const SITE_DISPLAY_TABLE = 'ucms_site_table_display';

    // Site initialization, happens at hook_boot()
    const EVENT_INIT = 'site:init';
    // Happens at hook_init() instead of hook_boot()
    const EVENT_POST_INIT = 'site:post-init';

    // No site was inited, and we are currently in admin
    const EVENT_MASTER_INIT = 'site:master-init';

    // Site context is being dropped
    const EVENT_DROP = 'site:drop';

    // Site access is requested
    const EVENT_ACCESS = 'site:access';
}
