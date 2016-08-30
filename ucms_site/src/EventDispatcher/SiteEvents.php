<?php

namespace MakinaCorpus\Ucms\Site\EventDispatcher;

final class SiteEvents
{
    // Stuff happens
    const EVENT_ATTACH = 'site:node.attach';
    const EVENT_DETACH = 'site:node.dettach';
    const EVENT_CLONE = 'site:clone';

    // Admin screens events
    const SITE_DISPLAY_TABLE = 'ucms_site_table_display';

    // Site initialization, happens at hook_boot().
    const EVENT_INIT = 'site:init';
    // Happens at hook_init() instead of hook_boot().
    const EVENT_POST_INIT = 'site:post-init';
}
