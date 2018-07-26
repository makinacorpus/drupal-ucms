<?php

namespace MakinaCorpus\Ucms\Dashboard\Action;

use MakinaCorpus\Ucms\Dashboard\Form\ActionProcessForm;

/**
 * Represent a possible action over a certain item, this is just a value
 * object that will be used to build UI links or buttons
 */
abstract class AbstractActionProcessor
{
    private $title;
    private $icon;
    private $priority = 0;
    private $isPrimary = false;
    private $isDangerous = false;
    private $isDialog = true;
    private $group;
    private $description;

    /**
     * Default constructor
     *
     * @param string $title
     * @param string $icon
     * @param int $priority
     * @param boolean $isPrimary
     * @param boolean $isDangerous
     * @param boolean $isDialog
     * @param string $description
     */
    public function __construct(string $title, $icon = null, int $priority = 0, bool $isPrimary = true, bool $isDangerous = false, bool $isDialog = true, string $group = Action::GROUP_DEFAULT, string $description = '')
    {
        $this->title = $title;
        $this->icon = $icon;
        $this->priority = $priority;
        $this->isPrimary = $isPrimary;
        $this->isDangerous = $isDangerous;
        $this->isDialog = $isDialog;
        $this->group = $group;
        $this->description = $description;
    }

    /**
     * Get unique string identifier that will be used for links
     */
    abstract public function getId(): string;

    /**
     * Does this provider applies to the given item
     *
     * This is the right place to apply access checks, and other stuff like
     * that. Make it very fast to execute, it might ran hundreds of time during
     * the same request!
     */
    abstract public function appliesTo($item): bool;

    /**
     * Get action question
     *
     * @param mixed[] $items
     *   A sample of the items to process
     * @param int $totalCount
     *   Total count of items to process, if count is different from the items
     *   list count, then you may set a message "... and X more to process" for
     *   example
     */
    abstract public function getQuestion($items, int $totalCount): string;

    /**
     * Process all provided items
     *
     * @param mixed[] $items
     *
     * @return string
     *   Human localized readable status message, can be empty
     */
    abstract public function processAll($items): string;

    /**
     * Get form class
     */
    public function getFormClass(): string
    {
        return ActionProcessForm::class;
    }

    /**
     * Process a single item
     *
     * @return string
     *   Human localized readable status message, can be empty
     */
    public function process($item): string
    {
        return $this->processAll([$item]);
    }

    /**
     * From the given item, get an identifier
     *
     * This will be used to build URLs.
     *
     * @param mixed $items
     *
     * @return int|string
     */
    abstract public function getItemId($item);

    /**
     * Load item from the identifier extracted by getItemId()
     *
     * @param int|string $id
     *
     * @return mixed
     */
    abstract public function loadItem($id);

    /**
     * Get ready to use action
     *
     * @param mixed $item
     *
     * @return Action
     *   Or null if not appliable
     */
    public function getAction($item)
    {
        $options = [
            'query' => [
                'item'      => $this->getItemId($item),
                'processor' => $this->getId(),
            ],
        ];
        if ($this->isLinkDialog()) {
            $options['attributes']['class'][] = 'use-ajax';
            $options['attributes']['class'][] = 'minidialog';
            $options['query']['minidialog'] = 1;
            $options['query'] += drupal_get_destination();
        }

        return new Action(
            $this->getLinkTitle(),
            'admin/action/process',
            $options,
            $this->getLinkIcon(),
            $this->getLinkPriority(),
            $this->isLinkPrimary(),
            true,
            false,
            $this->getLinkGroup()
        );
    }

    /**
     * Get action link title
     *
     * @return string
     */
    public function getLinkTitle(): string
    {
        return $this->title ?? '';
    }

    /**
     * Get action link priority
     *
     * @return int
     */
    public function getLinkPriority(): int
    {
        return $this->priority ?? 0;
    }

    /**
     * Get link icon
     *
     * @return string
     */
    public function getLinkIcon(): string
    {
        return $this->icon ?? '';
    }

    /**
     * Should the link open a dialog
     */
    public function isLinkDialog(): bool
    {
        return (bool)$this->isDialog;
    }

    /**
     * Is link primary
     */
    public function isLinkPrimary(): bool
    {
        return (bool)$this->isPrimary;
    }

    /**
     * Get action link group
     */
    public function getLinkGroup(): string
    {
        return $this->group ?? Action::GROUP_DEFAULT;
    }

    /**
     * Get action description, this is optionnal
     */
    public function getDescription(): string
    {
        return $this->description ?? '';
    }

    /**
     * Is this action dangerous
     *
     * This will only have UX/theming implications
     */
    public function isDangerous(): bool
    {
        return (bool)$this->isDangerous;
    }
}
