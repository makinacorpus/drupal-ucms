<?php

namespace MakinaCorpus\Ucms\Site\Action;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\Action\ActionProviderInterface;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\EventDispatcher\NodeReference;

/**
 * The site module will add node actions, corresponding to reference
 * and cloning operations
 */
class NodeReferenceActionProvider implements ActionProviderInterface
{
    use StringTranslationTrait;

    private $entityManager;

    /**
     * Default constructor
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * {inheritdoc}
     */
    public function getActions($item)
    {
        /** @var \MakinaCorpus\Ucms\Site\EventDispatcher\NodeReference $item */
        $ret = [];
        $nodeStorage = $this->entityManager->getStorage('node');
        $sourceId = $item->getSourceId();
        $targetId = $item->getTargetId();

        $ret[] = new Action($this->t("View source"), 'node/'.$sourceId, null, 'eye-open', 0, true);
        // Sorry, this is NOT performant
        if ($node = $nodeStorage->load($sourceId)) {
            if ($node->access(Access::OP_UPDATE)) {
                $ret[] = new Action($this->t("Edit source"), 'node/'.$sourceId.'/edit', null, 'pencil', 1, true);
            }
        }

        if ($item->targetExists() && ($target = $nodeStorage->load($targetId))) {
            if ($target->access(Access::OP_VIEW)) {
                $ret[] = new Action($this->t("View target"), 'node/'.$targetId, null, 'eye-open', 100, false);
                if ($target->access(Access::OP_UPDATE)) {
                    $ret[] = new Action($this->t("Edit target"), 'node/'.$targetId.'/edit', null, 'pencil', 200, false);
                }
            }
        }

        return $ret;
    }

    /**
     * {inheritdoc}
     */
    public function supports($item)
    {
        return $item instanceof NodeReference;
    }
}
