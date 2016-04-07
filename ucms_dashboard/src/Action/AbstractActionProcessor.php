<?php

namespace MakinaCorpus\Ucms\Dashboard\Action;

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
    public function __construct($title, $icon = null, $priority = 0, $isPrimary = true, $isDangerous = false, $isDialog = true, $description = null)
    {
        $this->title = $title;
        $this->icon = $icon;
        $this->priority = $priority;
        $this->isPrimary = $isPrimary;
        $this->isDangerous = $isDangerous;
        $this->isDialog = $isDialog;
        $this->description = $description;
    }

    /**
     * Get unique string identifier that will be used for links
     *
     * @return string
     */
    abstract public function getId();

    /**
     * Does this provider applies to the given item
     *
     * This is the right place to apply access checks, and other stuff like
     * that. Make it very fast to execute, it might ran hundreds of time during
     * the same request!
     *
     * @param mixed $item
     *
     * @return boolean
     */
    abstract public function appliesTo($item);

    /**
     * Get action question
     *
     * @param mixed[] $items
     *   A sample of the items to process
     * @param int $totalCount
     *   Total count of items to process, if count is different from the items
     *   list count, then you may set a message "... and X more to process" for
     *   example
     *
     * @return string
     */
    abstract public function getQuestion($items, $totalCount);

    /**
     * Process all provided items
     *
     * @param mixed[] $items
     */
    abstract public function processAll($items);

    /**
     * Process a single item
     */
    public function process($item)
    {
        $this->processAll([$item]);
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
            true
        );
    }

    /**
     * Get action link title
     *
     * @return string
     */
    public function getLinkTitle()
    {
        return $this->title;
    }

    /**
     * Get action link priority
     *
     * @return int
     */
    public function getLinkPriority()
    {
        return $this->priority;
    }

    /**
     * Get link icon
     *
     * @return string
     */
    public function getLinkIcon()
    {
        return $this->icon;
    }

    /**
     * Should the link open a dialog
     *
     * @return string
     */
    public function isLinkDialog()
    {
        return $this->isDialog;
    }

    /**
     * Is link primary
     *
     * @return boolean
     */
    public function isLinkPrimary()
    {
        return $this->isPrimary;
    }

    /**
     * Get action description, this is optionnal
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Is this action dangerous
     *
     * This will only have UX/theming implications
     *
     * @return boolean
     */
    public function isDangerous()
    {
        return $this->isDangerous;
    }
}
