<?php

namespace MakinaCorpus\Ucms\Site;

/**
 * Handles public static files domain rewrite on a dedicated domain
 *
 */
class UCPublicStreamWrapper extends DrupalPublicStreamWrapper
{

    

    /**
     * Overrides getExternalUrl().
     *
     * Return the HTML URI of a public file.
     * May set it on a CDN domain if asked to do so.
     */
    function getExternalUrl() {
        $use_cdn =& drupal_static('UCPublicStreamWrapper_use_cdn');
        if (!isset($use_cdn)) {
            $use_cdn = variable_get('ucms_site_use_cdn', False);
        }

        if ($use_cdn) {

            $cdn =& drupal_static('UCPublicStreamWrapper_cdn');
            if (!isset($cdn)) {
                $cdn = variable_get('ucms_site_cdn_hostname', $GLOBALS['base_url']);
            }

            $path = str_replace('\\', '/', $this->getTarget());

            return $cdn
                . '/' 
                . self::getDirectoryPath()
                . '/'
                . drupal_encode_path($path);

        } else {
            return parent::getExternalUrl();
        }
    }
}

}
