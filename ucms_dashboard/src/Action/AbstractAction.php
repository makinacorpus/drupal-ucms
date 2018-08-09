<?php

namespace MakinaCorpus\Ucms\Dashboard\Action;

use Drupal\Core\Language\LanguageInterface;

abstract class AbstractAction implements Action
{
    private $description = '';
    private $disabled = false;
    private $granted;
    private $grantedCallback;
    private $group = null;
    private $icon = '';
    private $id;
    private $primary = true;
    private $priority = 0;
    private $title = '';

    /**
     * Create from array
     */
    protected static function populate(AbstractAction $instance, string $id, array $options)
    {
        $instance->id = (string)$id;

        $instance->description = (string)($options['description'] ?? false);
        $instance->disabled = (string)($options['is_disabled'] ?? false);
        $instance->group = (string)($options['group'] ?? '');
        $instance->icon = (string)($options['icon'] ?? '');
        $instance->primary = (bool)($options['primary'] ?? false);
        $instance->priority = (int)($options['priority'] ?? 0);
        $instance->title = (string)($options['title'] ?? '');

        if (isset($options['is_granted'])) {
            $instance->setIsGranted($options['is_granted']);
        }

        return $instance;
    }

    /**
     * Explicit new is disallowed from the outside world.
     */
    protected function __construct()
    {
    }

    /**
     * Set is granted value or callback
     */
    final protected function setIsGranted($isGranted)
    {
        if (\is_callable($isGranted)) {
            $this->grantedCallback = $isGranted;
        } else {
            $this->granted = (bool)$isGranted;
        }
    }

    /**
     * {@inheritdoc}
     */
    final public function getId(): string
    {
        return $this->id ?? '';
    }

    /**
     * {@inheritdoc}
     */
    final public function getDrupalId(): string
    {
        // @todo I am not proud of this one (code from BlockBase).
        $transliterated = \Drupal::transliteration()->transliterate($this->title ?? $this->route, LanguageInterface::LANGCODE_DEFAULT, '_');
        $transliterated = \mb_strtolower($transliterated);
        $transliterated = \preg_replace('@[^a-z0-9_.]+@', '', $transliterated);

        return $transliterated;
    }

    /**
     * {@inheritdoc}
     */
    final public function getGroup(): string
    {
        return $this->group ?? self::GROUP_DEFAULT;
    }

    /**
     * {@inheritdoc}
     */
    final public function getTitle(): string
    {
        return $this->title ?? '';
    }

    /**
     * {@inheritdoc}
     */
    final public function getDescription(): string
    {
        return $this->description ?? '';
    }

    /**
     * {@inheritdoc}
     */
    final public function getIcon(): string
    {
        return $this->icon ?? '';
    }

    /**
     * {@inheritdoc}
     */
    final public function getPriority(): int
    {
        return $this->priority ?? 0;
    }

    /**
     * {@inheritdoc}
     */
    final public function isPrimary(): bool
    {
        return $this->primary;
    }

    /**
     * {@inheritdoc}
     */
    final public function isDisabled(): bool
    {
        return $this->isGranted() || $this->disabled;
    }

    /**
     * {@inheritdoc}
     */
    final public function isGranted(): bool
    {
        if (null === $this->granted) {
            if ($this->grantedCallback) {
                $this->granted = (bool)call_user_func($this->grantedCallback);
                // Release the callback from memory, this is an immutable object
                unset($this->grantedCallback);
            } else {
                $this->granted = false;
            }
        }

        return $this->granted;
    }
}
