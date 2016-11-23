<?php

namespace MakinaCorpus\Ucms\Site;

/**
 * Handles public static files domain rewrite on a dedicated domain
 */
class PublicStreamWrapper extends \DrupalPublicStreamWrapper
{
    private $useCdn = false;
    private $cdnUri;

    /**
     * Default constructor
     */
    public function __construct()
    {
        $this->useCdn = variable_get('ucms_site_use_cdn', false);
        $this->cdnUri = variable_get('ucms_site_cdn_uri', $GLOBALS['base_url']);
    }

    /**
     * Will override public:// files absolute URL to point toward the CDN site.
     *
     * {@inheritdoc}
     */
    public function getExternalUrl()
    {
        if ($this->useCdn) {
            $path = str_replace('\\', '/', $this->getTarget());

            return $this->cdnUri . '/' . self::getDirectoryPath() . '/' . drupal_encode_path($path);
        }

        return parent::getExternalUrl();
    }
}
