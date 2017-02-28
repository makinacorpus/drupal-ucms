<?php

namespace MakinaCorpus\Ucms\Seo\EventDispatcher;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteCloneEvent;

class SiteCloneEventListener
{
    use StringTranslationTrait;

    private $db;

    /**
     * Default constructor
     *
     * @param \DatabaseConnection $db
     */
    public function __construct(\DatabaseConnection $db) {
        $this->db = $db;
    }

    /**
     * On site cloning.
     *
     * @param SiteCloneEvent $event
     */
    public function onSiteClone(SiteCloneEvent $event)
    {
        $source = $event->getTemplateSite();
        $target = $event->getSite();

        // Duplicate aliases for template site nodes
        $this
          ->db
          ->query(
            "
                INSERT INTO {ucms_seo_alias} (
                    source, 
                    alias, 
                    language, 
                    site_id, 
                    is_canonical, 
                    priority, 
                    expires, 
                    node_id
                )
                SELECT
                    a.source,
                    a.alias,
                    a.language,
                    :target,
                    a.is_canonical,
                    a.priority,
                    a.expires,
                    a.node_id
                FROM {ucms_seo_alias} a
                WHERE
                    (a.site_id = :source)
            ",
            [
              ':source' => $source->getId(),
              ':target' => $target->getId(),
            ]
          );
    }
}
