<?php

namespace MakinaCorpus\Ucms\Composition;

/**
 * Handles regions configuration
 */
final class RegionConfig
{
    const CONTEXT_NONE = 0;
    const CONTEXT_PAGE = 1;
    const CONTEXT_SITE = 2;

    /**
     * Get the enabled regions of the given theme for the given context type
     *
     * @param string $theme
     * @param int $contextType
     *   One the ::CONTEXT_* constants of this class
     */
    public static function getThemeRegionConfigFor(string $theme, int $contextType) : array
    {
        return array_filter(
            variable_get('ucms_layout_regions_' . $theme, []),
            function ($value) use ($contextType) {
                return $value == $contextType;
            }
        );
    }

    /**
     * Get list of page regions for theme
     *
     * @param string $theme
     *
     * @return string[]
     */
    public static function getPageRegionList(string $theme) : array
    {
        return array_keys(self::getThemeRegionConfigFor($theme, self::CONTEXT_PAGE));
    }

    /**
     * Get list of transversal regions for theme
     *
     * @param string $theme
     *
     * @return string[]
     */
    public static function getSiteRegionList(string $theme) : array
    {
        return array_keys(self::getThemeRegionConfigFor($theme, self::CONTEXT_SITE));
    }
}
