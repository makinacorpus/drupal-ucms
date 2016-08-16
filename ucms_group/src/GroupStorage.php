<?php

namespace MakinaCorpus\Ucms\Group;

use MakinaCorpus\Ucms\Group\EventDispatcher\GroupEvent;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class GroupStorage
{
    private $database;
    private $dispatcher;

    /**
     * Default constructor
     *
     * @param \DatabaseConnection $database
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(\DatabaseConnection $database, EventDispatcherInterface $dispatcher = null)
    {
        $this->database = $database;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Load group by identifier
     *
     * @param int $id
     *
     * @return Group
     *
     * @throws \InvalidArgumentException
     */
    public function findOne($id)
    {
        $group = $this
            ->database
            ->query(
                "SELECT * FROM {ucms_group} WHERE id = :id LIMIT 1 OFFSET 0",
                [':id' => $id]
            )
            ->fetchObject(Group::class)
        ;

        if (!$group) {
            throw new \InvalidArgumentException("Group does not exists");
        }

        return $group;
    }

    /**
     * Load all groups using conditions
     *
     * @param array $conditions
     *   Keys are field names, values are either single values or list of values
     * @param string $orderField
     *   Field name
     * @param string $order
     *   'asc' or 'desc'
     * @param int $limit
     *
     * @param bool $withAccess
     * @return Group[]
     */
    protected function loadWithConditions($conditions = [], $orderField = null, $order = null, $limit = 100, $withAccess = true)
    {
        $ret = [];

        if (empty($conditions)) {
            return $ret;
        }

        $q = $this
            ->database
            ->select('ucms_group', 'g')
            ->fields('g')
        ;

        if ($withAccess) {
            $q->addTag('ucms_group_access');
        }

        foreach ($conditions as $field => $values) {
            // @todo handle date types
            // @todo handle wrong input (non existing fields)
            $q->condition('g.' . $field, $values);
        }

        if ($orderField) {
            $q->orderBy('g.' . $field, $order === 'desc' ? 'desc' : 'asc');
        }

        $groups = $q
            ->execute()
            ->fetchAll(\PDO::FETCH_CLASS, Group::class)
        ;

        foreach ($groups as $group) {
            $ret[$group->getId()] = $group;
        }

        return $ret;
    }

    /**
     * Dispatch an event
     *
     * @param Group $group
     * @param string $event
     * @param string $data
     * @param int $userId
     */
    private function dispatch(Group $group, $event, $data = [], $userId = null)
    {
        if (!$this->dispatcher) {
            return;
        }

        $this->dispatcher->dispatch('group:' . $event, new GroupEvent($group, $userId, $data));
    }

    /**
     * Load all groups from the given identifiers
     *
     * @param array $idList
     * @param string $withAccess
     *
     * @return Group[]
     */
    public function loadAll($idList = [], $withAccess = true)
    {
        $ret = [];

        if (empty($idList)) {
            return $ret;
        }

        $q = $this
            ->database
            ->select('ucms_group', 'g')
        ;

        if ($withAccess) {
            $q->addTag('ucms_group_access');
        }

        $groups = $q
            ->fields('g')
            ->condition('g.id', $idList)
            ->execute()
            ->fetchAll(\PDO::FETCH_CLASS, Group::class)
        ;

        // Ensure order is the same
        // FIXME: find a better way
        $sort = [];
        foreach ($groups as $group) {
            $sort[$group->getId()] = $group;
        }
        foreach ($idList as $id) {
            if (isset($sort[$id])) {
                $ret[$id] = $sort[$id];
            }
        }

        return $ret;
    }

    /**
     * Save given group
     *
     * If the given group has no identifier, its identifier will be set
     *
     * @param Group $group
     * @param array $fields
     *   If set, update only the given fields
     * @param int $userId
     *   Who did this!
     */
    public function save(Group $group, array $fields = null, $userId = null)
    {
        $eligible = [
            'title',
            'is_ghost',
            'is_meta',
            'ts_created',
            'ts_changed',
            'attributes',
        ];

        if (null === $fields) {
            $fields = $eligible;
        } else {
            $fields = array_intersect($eligible, $fields);
        }

        $values = [];
        foreach ($fields as $field) {
            switch ($field) {

                case 'attributes':
                    $attributes = $group->getAttributes();
                    if (empty($attributes)) {
                        $values[$field] = null;
                    } else {
                        $values[$field] = serialize($attributes);
                    }
                    break;

                case 'is_ghost':
                    $values['is_ghost'] = (int)$group->isGhost();
                    break;

                case 'is_meta':
                    $values['is_meta'] = (int)$group->isMeta();
                    break;

                default:
                    $values[$field] = $group->{$field}; // @todo uses __get() fixme
            }
        }

        $values['ts_changed'] = $group->touch()->format('Y-m-d H:i:s');

        if ($group->getId()) {
            $this->dispatch($group, 'preSave', [], $userId);

            $this
                ->database
                ->merge('ucms_group')
                ->key(['id' => $group->getId()])
                ->fields($values)
                ->execute()
            ;

            $this->dispatch($group, 'save', [], $userId);
        } else {
            $this->dispatch($group, 'preCreate', [], $userId);

            $values['ts_created'] = $values['ts_changed'];

            $id = $this
                ->database
                ->insert('ucms_group')
                ->fields($values)
                ->execute()
            ;

            $group->setId($id);

            $this->dispatch($group, 'create', [], $userId);
        }
    }

    /**
     * Delete the given group
     *
     * @param Group $group
     * @param int $userId
     *   Who did this!
     */
    public function delete(Group $group, $userId = null)
    {
        $this->dispatch($group, 'preDelete', [], $userId);

        $this->database->delete('ucms_group')->condition('id', $group->getId())->execute();

        $this->dispatch($group, 'delete', [], $userId);
    }
}
