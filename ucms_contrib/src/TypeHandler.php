<?php

namespace MakinaCorpus\Ucms\Contrib;


use Exception;

class TypeHandler
{
    /**
     * Cleans variable value
     *
     * @param $name
     * @return mixed
     */
    protected function filterVariable($name)
    {
        return array_filter(variable_get($name, []));
    }

    /**
     * Get tab list.
     *
     * @return array
     */
    public function getTabs()
    {
        return [
            'content' => "Content",
            'media'   => "Media",
        ];
    }

    /**
     * Given a tab name, get its corresponding types.
     *
     * @param $tab
     * @return \string[]
     * @throws Exception
     */
    public function getTabTypes($tab)
    {
        switch ($tab) {
            case 'content':
                return $this->getContentTypes();
            case 'media':
                return $this->getMediaTypes();
            default:
                throw new Exception("Tab not implemented");
        }
    }


    /**
     * Get all media types.
     *
     * @return string[]
     */
    public function getMediaTypes()
    {
        return $this->filterVariable('ucms_contrib_tab_media_type');
    }

    /**
     * Get editorial content types.
     *
     * @return string[]
     */
    public function getEditorialContentTypes()
    {
        return $this->filterVariable('ucms_contrib_editorial_types');
    }

    /**
     * Get component types.
     *
     * @return string[]
     */
    public function getComponentTypes()
    {
        return $this->filterVariable('ucms_contrib_component_types');
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
     * @param array $types
     */
    public function setMediaTypes(array $types)
    {
        variable_set('ucms_contrib_tab_media_type', $types);
    }

    /**
     * Set editorial content types.
     *
     * @param array $types
     */
    public function setEditorialContentTypes(array $types)
    {
        variable_set('ucms_contrib_editorial_types', $types);
    }

    /**
     * Set component types.
     * @param array $types
     */
    public function setComponentTypes(array $types)
    {
        variable_set('ucms_contrib_component_types', $types);
    }

    /**
     * Given an array of type, return the human-readable types keyed by type.
     *
     * @param array $types
     * @return mixed
     */
    public function getTypesAsHumanReadableList(array $types)
    {
        return array_intersect_key(node_type_get_names(), drupal_map_assoc($types));
    }
}
