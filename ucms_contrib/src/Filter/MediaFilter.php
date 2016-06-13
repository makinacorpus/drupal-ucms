<?php

namespace MakinaCorpus\Ucms\Contrib\Filter;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

class MediaFilter extends FilterBase implements ContainerFactoryPluginInterface
{
    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition)
    {
        return new static(
            $configuration,
            $pluginId,
            $pluginDefinition,
            $container->get('entity.manager'),
            $container->get('logger.channel.default'),
            $container->getParameter('ucms_contrib.filter.view_mode.markup')
        );
    }

    private $entityManager;
    private $logger;
    private $viewMode = 'default';

    /**
     * Default constructor
     *
     * @param mixed[] $configuration
     * @param string $pluginId
     * @param string $pluginDefinition
     * @param EntityManager $entityManager
     * @param LoggerChannelInterface $logger
     * @param string $viewMode
     */
    public function __construct(
        array $configuration,
        $pluginId,
        $pluginDefinition,
        EntityManager $entityManager,
        LoggerChannelInterface $logger = null,
        $viewMode = 'default')
    {
        parent::__construct($configuration, $pluginId, $pluginDefinition);

        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->viewMode = $viewMode;
    }

    /**
     * @param string $text
     *
     * @return \DOMDocument
     */
    protected function getDocumentFromHtml($text)
    {
        $d = new \DOMDocument();

        // Originally fixed by Yannick converting everything to HTML_ENTITIES
        // but this may break HTML behaviour at some point, so just switching
        // the xml header to utf-8 encoding
        // @see http://stackoverflow.com/a/8218649
        if (!@$d->loadHTML('<?xml encoding="utf-8" ?><!DOCTYPE html><html><body>' . $text . '</body></html>')) {
            $this->logger->error("markup contain invalid HTML, cannot parse medias");

            return;
        }

        return $d;
    }

    /**
     * @param \DOMElement $element
     * @param string $text
     */
    protected function setInnerHtml(\DOMElement $element, $text)
    {
        if (!$d = $this->getDocumentFromHtml($text)) {
            $this->logger->error("markup contain invalid HTML, cannot parse medias");

            return false;
        }

        $nodeList = $d->getElementsByTagName('body');
        foreach ($nodeList as $node) {
            foreach ($node->childNodes as $child) {
                $copy = $element->ownerDocument->importNode($child, true);
                $element->appendChild($copy);
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function process($text, $langcode)
    {
        // We cannot proceed with a regex because the WYSIWYG editor might leave
        // complete rendered nodes, anyway it also may fix some other wrong HTML
        // so just use domdocument.
        if (!$d = $this->getDocumentFromHtml($text)) {
            $this->logger->error("markup contain invalid HTML, cannot parse medias");

            return new FilterProcessResult($text);
        }

        $done = false;
        $map = [];

        // Find any element containing data-media-nid, extract it and replace it
        // with the associated loaded node.
        $xpath = new \DOMXPath($d);
        $nodeList = $xpath->query('//*[@data-media-nid]');
        foreach ($nodeList as $node) {
            /** @var $node \DOMElement */
            $nodeId = $node->getAttribute('data-media-nid');
            $map[$nodeId][] = [
                $node,
                $node->getAttribute('data-media-width'),
                $node->getAttribute('data-media-float'),
            ];
        }

        $nodes = $this->entityManager->getStorage('node')->loadMultiple(array_keys($map));
        foreach ($map as $nodeId => $dataList) {
            $done = true;

            // If node is not loaded, it means it does not exists anymore
            // we need to replace this particular node in the DOMDocument
            // with nothing
            if (!isset($nodes[$nodeId])) {
                foreach ($dataList as $data) {
                    /** @var $node \DOMNode */
                    list($node) = $data;

                    $node->parentNode->removeChild($node);
                    $this->logger->info(sprintf("node '%d' does not exist anymore", $nodeId));
                }
                continue;
            }

            // Render the node only once
            // @todo node_view() is not d8 friendly
            $renderedMedia = node_view($nodes[$nodeId], $this->viewMode);
            $renderedMedia = drupal_render($renderedMedia);

            // Normal procedure, render the node and put it there
            foreach ($dataList as $data) {
                list($node, $width, $float) = $data;

                $new = $d->createElement('div');
                $new->setAttribute('class', 'body-media');
                 $this->setInnerHtml($new, $renderedMedia);

                $new->setAttribute('data-media-nid', $nodeId);
                $new->setAttribute('data-media-width', $width);
                $new->setAttribute('data-media-float', $float);
                if ($width) {
                    $new->setAttribute('style', 'width: ' . $width);
                }
                if ($float) {
                    $new->setAttribute('class', 'pull-' . $float);
                }

                /** @var $parent \DOMNode */
                $parent = $node->parentNode;
                $parent->replaceChild($new, $node);
            }
        }

        if ($done) {
            $nodeList = $d->getElementsByTagName('body');
            foreach ($nodeList as $node) {

                $text = '';
                foreach ($node->childNodes as $child) {
                    $text .= $d->saveHTML($child);
                }

                return new FilterProcessResult($text);
            }

            // Something bad happend.
            $this->logger->error("could not save generated HTML");
        }

        return new FilterProcessResult($text);
    }
}
