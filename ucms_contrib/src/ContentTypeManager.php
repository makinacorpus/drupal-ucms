<?php

namespace MakinaCorpus\Ucms\Contrib;

use MakinaCorpus\Ucms\Contrib\EventDispatcher\BehaviorCollectionEvent;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ContentTypeManager
{
    /**
     * Get service name for page type
     *
     * @todo unstatic this
     *
     * @param string $tab
     *   'content' or 'media' or anything that the type handler knows about
     * @param string $page
     *   'mine', 'global', etc...
     *
     * @return string
     */
    static public function getServiceName($tab, $page)
    {
        return 'ucms_contrib.page_type.' . $tab . '.' . $page;
    }

    /**
     * @var \DatabaseConnection
     */
    private $database;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    private $tabs = [];
    private $adminPages = [];

    private $cacheBehaviorsToTypes = [];
    private $behaviorsToTypesFullyLoaded = false;

  /**
   * ContentTypeManager constructor.
   *
   * @param \DatabaseConnection $database
   * @param EventDispatcherInterface $eventDispatcher
   * @param string[] $tabs
   *   Keys are path component, values are names
   * @param string[] $adminPages
   *   Keys are path component, values are names
   */
    public function __construct(
        \DatabaseConnection $database,
        EventDispatcherInterface $eventDispatcher,
        array $tabs = [],
        array $adminPages = []
    ) {
        $this->database = $database;
        $this->eventDispatcher = $eventDispatcher;
        $this->tabs = $tabs;
        $this->adminPages = $adminPages;
    }

    /**
     * Get tab list.
     *
     * @return array
     */
    public function getTabs()
    {
        return $this->tabs;
    }

    /**
     * Get admin pages definitions
     *
     * @todo
     *   - how to handler permission for those pages?
     *   - tie permissions with Drupal menu system
     *   - better an a variable, what could it be?
     *   - tabs content types could be configured too?
     *
     * @return string[]
     *   Keys are path component, values are names.
     */
    public function getAdminPages()
    {
        return $this->adminPages;
    }

    /**
     * Given a tab name, get its corresponding types.
     *
     * @param string $tab
     *
     * @return string[]
     *
     * @throws \Exception
     *   If tab identifier is unknown.
     */
    public function getTabTypes($tab)
    {
        switch ($tab) {
            case 'content':
                return $this->getNonMediaTypes();
            case 'media':
                return $this->getMediaTypes();
            default:
                throw new \Exception("Tab not implemented");
        }
    }

    /**
     * Collects available content types behaviors.
     *
     * @return []
     */
    public function collectBehaviors()
    {
        $event = new BehaviorCollectionEvent();
        $this->eventDispatcher->dispatch(BehaviorCollectionEvent::EVENT_NAME, $event);
        return $event->getBehaviors();
    }

    /**
     * Provides all content types associated with their behaviors.
     *
     * @return []
     */
//    public function loadBehaviorsForAll()
//    {
//        if (!$this->allBehaviorsLoaded) {
//            $query = $this->database->select('ucms_contrib_behavior', 'b');
//            $query->rightJoin('node_type', 'nt', "nt.type = b.type");
//            $query->addField('nt', 'type');
//            $query->addField('b', 'behavior');
//
//            $this->cacheTypeToBehaviors = $query->execute()->fetchAllKeyed();
//            $this->allBehaviorsLoaded = true;
//        }
//
//        return $this->cacheTypeToBehaviors;
//    }

    /**
     * Provides the list of behaviors associated with the given content type.
     *
     * @param string $type
     *   Content type identifier.
     *
     * @return string[]
     */
    public function loadBehaviorsForType($type)
    {
        return $this->database
            ->select('ucms_contrib_behavior', 'b')
            ->fields('b', ['behavior'])
            ->condition('b.type', $type)
            ->execute()
            ->fetchCol()
        ;
    }

    /**
     * Saves associations between content type and behaviors.
     *
     * @param string $type
     *   Content type identifier.
     * @param string|string[] $behaviors
     *   Behavior identifiers.
     * @param boolean $reset
     *   If the currently saved behaviors must be removed
     *   before saved the new ones.
     */
    public function saveBehaviorsForType($type, $behaviors, $reset = false)
    {
        $behaviors = (array) $behaviors;
        $current = $this->loadBehaviorsForType($type);

        if ($reset && !empty($current)) {
            $toRemove = array_diff($current, $behaviors);
            if ($toRemove) {
                $this->removeBehaviorsForType($type, $toRemove);
            }
        }

        $query = $this->database
            ->insert('ucms_contrib_behavior')
            ->fields(['type', 'behavior'])
        ;

        foreach (array_diff($behaviors, $current) as $behavior) {
            $query->values([$type, $behavior]);
        }

        $query->execute();
    }

    /**
     * Removes some behaviors from a content type.
     *
     * @param string $type
     *   Content type identifier.
     * @param string|string[]|null $behaviors
     *   Behavior identifiers.
     */
    public function removeBehaviorsForType($type, $behaviors = null)
    {
        $query = $this->database
            ->delete('ucms_contrib_behavior')
            ->condition('type', $type)
        ;

        if ($behaviors !== null) {
            $query->condition('behavior', $behaviors);
        }

        $query->execute();
    }

    /**
     * Provides all behaviors associated with their content types.
     *
     * @return string[]
     */
    public function loadTypesForAll()
    {
        if (!$this->behaviorsToTypesFullyLoaded) {
            $query = $this->database->select('ucms_contrib_behavior', 'b');
            $query->rightJoin('node_type', 'nt', "nt.type = b.type");
            $query->addField('b', 'behavior');
            $query->addField('nt', 'type');

            $this->cacheBehaviorsToTypes = $query->execute()->fetchAllKeyed();
            $this->behaviorsToTypesFullyLoaded = true;
        }

        return $this->cacheBehaviorsToTypes;
    }

    /**
     * Provides the list of content types having the given behavior.
     *
     * @param string $behavior
     *   Behavior identifier
     *
     * @return string[]
     */
    public function getTypesWithBehavior($behavior)
    {
        if (!isset($this->cacheBehaviorsToTypes[$behavior])) {
            $this->cacheBehaviorsToTypes[$behavior] = $this->database
                ->select('ucms_contrib_behavior', 'b')
                ->fields('b', ['type'])
                ->condition('b.behavior', $behavior)
                ->execute()
                ->fetchCol()
            ;
        }

        return $this->cacheBehaviorsToTypes[$behavior];
    }

    /**
     * Is the given type associated with the given behavior.
     *
     * @param string $type
     *   Content type identifier
     * @param string $behavior
     *   Behavior identifier
     *
     * @return boolean
     */
    public function hasBehavior($type, $behavior)
    {
        $types = $this->getTypesWithBehavior($behavior);
        return in_array($type, $types);
    }

    /**
     * Resets the behaviors information cache.
     */
    public function resetBehaviorsCache()
    {
        $this->cacheBehaviorsToTypes = [];
        $this->behaviorsToTypesFullyLoaded = false;
    }

    /**
     * Provides the list of content types having all the given behaviors.
     *
     * @param string[] $behaviors Behaviors identifiers
     *
     * @return string[]
     */
//    public function getTypesWithBehaviors(array $behaviors)
//    {
//        switch (count($behaviors)) {
//            case 0: return [];
//            case 1: return $this->getTypesWithBehavior($behaviors[0]);
//        }
//
//        $arrays = [];
//        foreach ($behaviors as $behavior) {
//            $arrays[] = $this->getTypesWithBehavior($behavior);
//        }
//
//        return call_user_func_array('array_intersect', $arrays);
//    }

    /**
     * Get all content types.
     *
     * @return string[]
     */
    public function getAllTypes()
    {
        return array_keys(node_type_get_types());
    }

    /**
     * Given an array of type, return the human-readable types keyed by type.
     *
     * @param string[] $types
     *
     * @return string[]
     */
    public function getTypeNames(array $types = null)
    {
        if ($types === null) {
            return node_type_get_names();
        }

        return array_intersect_key(node_type_get_names(), drupal_map_assoc($types));
    }

    /**
     * Get editorial content types.
     *
     * @return string[]
     */
    public function getEditorialTypes()
    {
        return $this->getTypesWithBehavior('appear_as_editorial');
    }

    /**
     * Get media content types.
     *
     * @return string[]
     */
    public function getMediaTypes()
    {
        return $this->getTypesWithBehavior('appear_as_media');
    }

    /**
     * Get component content types.
     *
     * @return string[]
     */
    public function getComponentTypes()
    {
        return $this->getTypesWithBehavior('appear_as_component');
    }

    /**
     * Get non component types (editorial + media types).
     *
     * @return string[]
     */
    public function getNonComponentTypes()
    {
        return array_merge($this->getEditorialTypes(), $this->getMediaTypes());
    }

    /**
     * Get non media types (editorial + component types).
     *
     * @return string[]
     */
    public function getNonMediaTypes()
    {
        return array_merge($this->getEditorialTypes(), $this->getComponentTypes());
    }

    /**
     * Get locked types.
     *
     * @return string[]
     */
    public function getLockedTypes()
    {
        return $this->getTypesWithBehavior('locked');
    }

    /**
     * Get non locked types.
     *
     * @return string[]
     */
    public function getUnlockedTypes()
    {
        return array_diff($this->getAllTypes(), $this->getLockedTypes());
    }

    /**
     * Set editorial content types.
     *
     * @param string[] $types
     */
    public function setEditorialTypes(array $types)
    {
        $this->saveBehaviorsForType($types, 'appear_as_editorial', true);
    }

    /**
     * Set all media types.
     *
     * @param string[] $types
     */
    public function setMediaTypes(array $types)
    {
        $this->saveBehaviorsForType($types, 'appear_as_media', true);
    }

    /**
     * Set component types.
     *
     * @param string[] $types
     */
    public function setComponentTypes(array $types)
    {
        $this->saveBehaviorsForType($types, 'appear_as_component', true);
    }

    /**
     * Set component types.
     *
     * @param string[] $types
     */
    public function setLockedTypes(array $types)
    {
        $this->saveBehaviorsForType($types, 'locked', true);
    }
}
