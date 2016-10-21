<?php

namespace MakinaCorpus\Ucms\SmartUI\Action;

use Drupal\Core\Ajax\AjaxResponse;

use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\Action\ActionProcessorInterface;
use MakinaCorpus\Ucms\Dashboard\SmartObject;

/**
 * Class AbstractAjaxProcessor
 * @package MakinaCorpus\Ucms\SmartUI\Action
 */
abstract class AbstractAjaxProcessor implements ActionProcessorInterface
{
    private $title;
    private $icon;
    private $priority = 0;
    private $isDialog = true;

    /**
     * Default constructor
     *
     * @param string $title
     * @param string $icon
     * @param int $priority
     * @param boolean $isDialog
     */
    public function __construct(
        $title,
        $icon = null,
        $priority = 0,
        $isDialog = false
    ) {
        $this->title = $title;
        $this->icon = $icon;
        $this->priority = $priority;
        $this->isDialog = $isDialog;
    }

    /**
     * Get unique string identifier that will be used for links
     *
     * @return string
     */
    public function getId()
    {
        $first = true;

        $closure = function ($matches) use (&$first) {
            $ret = ($first ? '' : '_').strtolower($matches[0]);
            $first = false;

            return $ret;
        };

        $class = (new \ReflectionClass($this))->getShortName();

        return str_replace('_processor', '', preg_replace_callback("@[A-Z]@", $closure, $class));
    }

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
     * Process item
     *
     * @param SmartObject $item
     * @param AjaxResponse $response
     */
    abstract public function process($item, AjaxResponse $response);

    /**
     * From the given item, get an identifier
     *
     * This will be used to build URLs.
     *
     * @param SmartObject $item
     *
     * @return int|string
     */
    public function getItemId($item)
    {
        return $item->getContext().':'.$item->getNode()->id();
    }

    /**
     * From the given item, get an identifier
     *
     * This will be used to build URLs.
     *
     * @param SmartObject $item
     *
     * @return int|string
     */
    public function loadItem($item)
    {
        list($context, $nid) = explode(':', $item);

        // FIXME: should use the NodeStorage
        return new SmartObject(node_load($nid), $context);
    }

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
        $options['attributes']['class'][] = 'use-ajax';

        if ($this->isLinkDialog()) {
            $options['attributes']['class'][] = 'minidialog';
            $options['query']['minidialog'] = 1;
            $options['query'] += drupal_get_destination();
        }

        return new Action(
            $this->getLinkTitle(),
            'admin/ajax/process',
            $options,
            $this->getLinkIcon(),
            $this->getLinkPriority(),
            false
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
}
