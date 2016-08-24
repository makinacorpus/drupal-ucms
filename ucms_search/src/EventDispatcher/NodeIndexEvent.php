<?php

namespace MakinaCorpus\Ucms\Search\EventDispatcher;

use Drupal\node\NodeInterface;

use Symfony\Component\EventDispatcher\Event;

class NodeIndexEvent extends Event
{
    private $node;
    private $values;

    const FIELD_BODY = 'body';
    const FIELD_TAGS = 'tags';

    public function __construct(NodeInterface $node, $values = [])
    {
        $this->node = $node;
        $this->values = $values;
    }

    final public function getNode()
    {
        return $this->node;
    }

    final public function add($field, $value)
    {
        if (!isset($this->values[$field])) {
            $this->values[$field] = [];
        } else if (!is_array($this->values[$field])) {
            $this->values[$field] = [$this->values[$field]];
        }

        $this->values[$field][] = $value;
    }

    final public function set($field, $value)
    {
        $this->values[$field] = $value;
    }

    final public function remove($field)
    {
        unset($this->values[$field]);
    }

    final public function getValues()
    {
        return $this->values;
    }

    /**
     * {@inheritdoc}
     */
    public function fieldToFulltext($name, $target = self::FIELD_BODY)
    {
        if (field_get_items('node', $this->node, $name)) {
            $build = field_view_field('node', $this->node, $name, 'full');

            $this->add($target, drupal_render($build));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function fieldToTagIdList($name, $target = self::FIELD_TAGS)
    {
        if ($items = field_get_items('node', $this->node, $name)) {
            foreach ($items as $item) {
                if (isset($item['tid'])) {
                    $this->add($target, (int)$item['tid']);
                }
            }
        }
    }
}
