<?php

namespace MakinaCorpus\Ucms\Dashboard\Table;

use Drupal\Core\Render\Markup;
use MakinaCorpus\Ucms\Dashboard\EventDispatcher\AdminTableEvent;
use MakinaCorpus\Ucms\Site\Structure\AttributesTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AdminTable
{
    use AttributesTrait;

    private $name;
    private $currentSection;
    private $eventDispatcher;
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
    public function __construct(string $name = 'admin_details', array $attributes = [], EventDispatcherInterface $eventDispatcher = null)
    {
        $this->name = $name;
        $this->attributes = $attributes;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function hasSection(string $key): bool
    {
        return isset($this->sections[$key]);
    }

    public function getSection(string $key): AdminTableSection
    {
        if (!isset($this->sections[$key])) {
            throw new \InvalidArgumentException(sprintf("section '%s' does not exists in table '%s'", $key, $this->name));
        }

        return $this->sections[$key];
    }

    public function removeSection(string $key): self
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
    public function addHeader(string $label, string $key = null): self
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

    public function addRow(string $label, $value, string $key = null): self
    {
        if (!$this->currentSection) {
            $this->addHeader(null);
        }

        $this->currentSection->addRow($label, $value, $key);

        return $this;
    }

    public function render(): array
    {
        if ($this->eventDispatcher) {
            $this->eventDispatcher->dispatch('admin:table:' . $this->name, new AdminTableEvent($this));
        }

        $rows = [];

        // @todo make this go away into real templates (fouque)
        foreach ($this->sections as $section) {

            $title = $section->getTitle();

            if ($title) {
                $rows[] = [['data' => Markup::create('<strong>' . $title . '</strong>'), 'colspan' => 2]];
            }
            foreach ($section->getAllRows() as $row) {
                $rows[] = \array_map(function ($value) {
                    if (\is_string($value)) {
                        return Markup::create($value);
                    }
                    return $value;
                }, $row);
            }
        }

        return [
            '#theme' => 'table__' . $this->name,
            '#rows' => $rows,
        ];
    }
}
