<?php

namespace MakinaCorpus\Ucms\Contrib;

final class TypeHandler
{
    const TAB_CONTENT = 'content';
    const TAB_MEDIA = 'media';

    const TYPE_COMPONENT = 'component';
    const TYPE_EDITORIAL = 'editorial';
    const TYPE_MEDIA = 'media';

    private $metaTypeMap;

    /**
     * Default constructor
     */
    public function __construct()
    {
        $this->rebuildCache();
    }

    /**
     * Cleans variable value
     *
     * @param $name
     * @return mixed
     */
    private function filterVariable($name)
    {
        return array_filter(variable_get($name, []));
    }

    /**
     * Rebuild meta type cache
     */
    private function rebuildCache()
    {
        $this->metaTypeMap = [
            self::TYPE_COMPONENT => $this->filterVariable('ucms_contrib_component_types'),
            self::TYPE_EDITORIAL => $this->filterVariable('ucms_contrib_editorial_types'),
            self::TYPE_MEDIA => $this->filterVariable('ucms_contrib_tab_media_type'),
        ];
    }

    /**
     * Get tab list.
     *
     * @return array
     */
    public function getTabs()
    {
        return [
            self::TAB_CONTENT => "Content",
            self::TAB_MEDIA => "Media",
        ];
    }

    /**
     * Given a tab name, get its corresponding types.
     *
     * @param $tab
     *
     * @return string[]
     */
    public function getTabTypes($tab)
    {
        switch ($tab) {
            case self::TAB_CONTENT:
                return $this->getContentTypes();
            case self::TAB_MEDIA:
                return $this->getMediaTypes();
            default:
                throw new \Exception("Tab not implemented");
        }
    }

    /**
     * Get content type meta type
     */
    public function getMetaType(string $contentType) : string
    {
    }

    /**
     * Get all media types.
     *
     * @return string[]
     */
    public function getMediaTypes() : array
    {
        return $this->metaTypeMap[self::TYPE_MEDIA];
    }

    /**
     * Get editorial content types.
     *
     * @return string[]
     */
    public function getEditorialContentTypes() : array
    {
        return $this->metaTypeMap[self::TYPE_EDITORIAL];
    }

    /**
     * Get component types.
     *
     * @return string[]
     */
    public function getComponentTypes() : array
    {
        return $this->metaTypeMap[self::TYPE_COMPONENT];
    }

    /**
     * Get component types.
     *
     * @return string[]
     */
    public function getLockedTypes()  : array
    {
        return $this->filterVariable('ucms_contrib_locked_types');
    }

    /**
     * Get all other types than components.
     *
     * @return string[]
     */
    public function getUnlockedTypes()
    {
        return array_diff($this->getAllTypes(), $this->filterVariable('ucms_contrib_locked_types'));
    }

    /**
     * Get all  types.
     *
     * @return string[]
     */
    public function getAllTypes()
    {
        return array_merge($this->getContentTypes(), $this->getMediaTypes());
    }

    /**
     * Get all editorial (media + editorial content) types.
     *
     * @return string[]
     */
    public function getEditorialTypes()
    {
        return array_merge($this->getEditorialContentTypes(), $this->getMediaTypes());
    }

    /**
     * Get all content types.
     *
     * @return string[]
     */
    public function getContentTypes()
    {
        return array_merge($this->getComponentTypes(), $this->getEditorialContentTypes());
    }

    /**
     * Set all media types.
     *
     * @param string[] $types
     */
    public function setMediaTypes(array $types)
    {
        variable_set('ucms_contrib_tab_media_type', $types);
        $this->rebuildCache();
    }

    /**
     * Set editorial content types.
     *
     * @param string[] $types
     */
    public function setEditorialContentTypes(array $types)
    {
        variable_set('ucms_contrib_editorial_types', $types);
        $this->rebuildCache();
    }

    /**
     * Set component types.
     *
     * @param string[] $types
     */
    public function setComponentTypes(array $types)
    {
        variable_set('ucms_contrib_component_types', $types);
        $this->rebuildCache();
    }

    /**
     * Set component types.
     *
     * @param string[] $types
     */
    public function setLockedTypes(array $types)
    {
        variable_set('ucms_contrib_locked_types', $types);
        $this->rebuildCache();
    }

    /**
     * Get content type human readable label
     */
    public function getTypeLabel(string $contentType) : string
    {
        return \node_type_get_names()[$contentType] ?? '';
    }

    /**
     * Given an array of type, return the human-readable types keyed by type.
     *
     * @param string[] $types
     *
     * @return string[]
     */
    public function getTypesAsHumanReadableList(array $types) : array
    {
        return array_intersect_key(node_type_get_names(), drupal_map_assoc($types));
    }
}
