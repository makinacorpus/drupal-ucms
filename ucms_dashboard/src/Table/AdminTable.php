<?php

namespace MakinaCorpus\Ucms\Dashboard\Table;

use MakinaCorpus\Ucms\Dashboard\EventDispatcher\AdminTableEvent;
use MakinaCorpus\Ucms\Site\Structure\AttributesTrait;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AdminTable
{
    use AttributesTrait;

    private $name;
    private $currentSection;
    private $eventDispatcher;

    /**
     * @var AdminTableSection[]
     */
    private $sections = [];

    /**
     * Default constructor
     *
     * @param string $name
     *   Name will be the template suggestion, and the event name, where the
     *   event name will be admin:table:NAME
     * @param mixed[] $attributes
     *   Attributes that event listeners might fetch
     * @param EventDispatcherInterface $eventDispatcher
     *   Event dispatcher
     */
    public function __construct($name = 'admin_details', array $attributes = [], EventDispatcherInterface $eventDispatcher = null)
    {
        $this->name = $name;
        $this->attributes = $attributes;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function hasSection($key)
    {
        return isset($this->sections[$key]);
    }

    public function getSection($key)
    {
        if (!isset($this->sections[$key])) {
            throw new \InvalidArgumentException(sprintf("section '%s' does not exists in table '%s'", $key, $this->name));
        }

        return $this->sections[$key];
    }

    public function removeSection($key)
    {
        $this->getSection($key);

        unset($this->sections[$key]);

        return $this;
    }

    /**
     * Add a new section, set the current internal pointer to the new section
     *
     * @param string $label
     * @param string $key
     *
     * @return $this
     */
    public function addHeader($label, $key = null)
    {
        $section = new AdminTableSection($label, $key);

        if ($key) {
            $this->sections[$key] = $section;
        } else {
            $this->sections[] = $section;
        }

        $this->currentSection = $section;

        return $this;
    }

    public function addRow($label, $value, $key = null)
    {
        if (!$this->currentSection) {
            $this->addHeader(null);
        }

        $this->currentSection->addRow($label, $value, $key);

        return $this;
    }

    public function render()
    {
        if ($this->eventDispatcher) {
            $this->eventDispatcher->dispatch('admin:table:' . $this->name, new AdminTableEvent($this));
        }

        $rows = [];

        // @todo make this go away into real templates (fouque)
        foreach ($this->sections as $section) {

            $title = $section->getTitle();

            if ($title) {
                $rows[] = [['data' => '<strong>' . $title . '</strong>', 'colspan' => 2]];
            }
            foreach ($section->getAllRows() as $row) {
                $rows[] = $row;
            }
        }

        return [
            '#theme'  => 'table__' . $this->name,
            '#rows'   => $rows,
        ];
    }
}
