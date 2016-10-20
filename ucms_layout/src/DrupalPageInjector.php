<?php

namespace MakinaCorpus\Ucms\Layout;

use Drupal\Core\Entity\EntityManager;
use Drupal\node\NodeInterface;
use MakinaCorpus\Ucms\Dashboard\Action\ActionRegistry;
use MakinaCorpus\Ucms\Dashboard\SmartObject;

class DrupalPageInjector
{
    /**
     * @var ContextManager $contextManager
     */
    private $contextManager;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var int[]
     */
    private $nidList = [];

    /**
     * @var NodeInterface[]
     */
    private $nodes = [];

    /**
     * @var \MakinaCorpus\Ucms\Dashboard\Action\ActionProviderInterface
     */
    private $actionRegistry;

    /**
     * Default constructor
     *
     * @param ContextManager $contextManager
     * @param EntityManager $entityManager
     * @param \MakinaCorpus\Ucms\Dashboard\Action\ActionRegistry $actionRegistry
     */
    public function __construct(ContextManager $contextManager, EntityManager $entityManager,
        ActionRegistry $actionRegistry)
    {
        $this->contextManager = $contextManager;
        $this->entityManager = $entityManager;
        $this->actionRegistry = $actionRegistry;
    }

    /**
     * Collect (preload) all visible nodes
     *
     * @param \MakinaCorpus\Ucms\Layout\Context $context
     * @param array $filter
     * @return \int[]|null
     */
    private function collectNodeIdList(Context $context, $filter = [])
    {
        if (empty($filter)) {
            return [];
        }

        $layout = $context->getCurrentLayout();
        if (!$layout) {
            return null;
        }

        foreach ($layout->getAllRegions() as $name => $region) {
            if (isset($filter[$name])) {
                foreach ($region as $item) {
                    /* @var $item Item */
                    $this->nidList[] = $item->getNodeId();
                }
            }
        }

        return $this->nidList;
    }

    /**
     * @param $nodeId
     * @return \Drupal\node\NodeInterface $node
     *   Can be null
     */
    private function getNode($nodeId)
    {
        if (isset($this->nodes[$nodeId])) {
            return $this->nodes[$nodeId];
        }
        return null;
    }

    /**
     * Proccess context
     *
     * @param array $page
     * @param string $theme
     * @param Context $context
     * @param int $contextType
     */
    private function processContext(&$page, $theme, Context $context, $contextType)
    {
        $layout     = $context->getCurrentLayout();
        $isEditMode = $context->isTemporary();

        if (!$layout) {
            return;
        }

        if ($isEditMode) {
            // @todo drupal_add_js() to be removed
            drupal_add_js(['ucmsLayout' => ['editToken' => $context->getToken(), 'layoutId'  => $layout->getId()]], 'setting');
        }

        foreach ($this->contextManager->getThemeRegionConfig($theme) as $name => $regionStatus) {

            if ($contextType !== (int)$regionStatus) {
                continue; // This region does not belong to us
            }

            // Preload all nodes for performance.
            $items      = [];
            $region     = $layout->getRegion($name);

            /* @var $item Item */
            foreach ($region as $item) {
                // Nodes are preloaded before
                $node = $this->getNode($item->getNodeId());
                if ($node && $node->access('view')) {
                    $items[] = [
                        '#theme'     => 'ucms_layout_item',
                        '#nid'       => $item->getNodeId(),
                        '#node'      => $node,
                        '#view_mode' => $item->getViewMode(),
                        '#region'    => $region,
                        'icons'      => $this->actionRegistry->getActions(new SmartObject($node, SmartObject::CONTEXT_LAYOUT)),
                    ];
                }
            }

            if ($isEditMode && empty($items) && empty($page[$name])) {
                // Trick block.module so that it's not "empty"
                $items = ['#markup' => ''];
            }

            if ($items) {
                $page[$name] = $items;
            }
        }
    }

    /**
     * Inject given regions into the given page
     *
     * @param array $page
     * @param $theme
     */
    public function inject(&$page, $theme)
    {
        $contextes = [
            ContextManager::CONTEXT_PAGE => $this->contextManager->getPageContext(),
            ContextManager::CONTEXT_SITE => $this->contextManager->getSiteContext()
        ];

        // Preload all node at onces
        foreach ($contextes as $contextType => $context) {
            $this->collectNodeIdList($context, $this->contextManager->getThemeRegionConfigFor($theme, $contextType));
        }
        if ($this->nidList) {
            $this->nodes = $this->entityManager->getStorage('node')->loadMultiple($this->nidList);
        }

        foreach ($contextes as $contextType => $context) {
            $this->processContext($page, $theme, $context, $contextType);
        }
    }
}
