<?php

namespace MakinaCorpus\Ucms\Layout\Drop;

use Drupal\Core\Entity\EntityManager;

use MakinaCorpus\Ucms\Drop\DragItem;
use MakinaCorpus\Ucms\Drop\DropStatus;
use MakinaCorpus\Ucms\Drop\Impl\AbstractNodeDropHandler;
use MakinaCorpus\Ucms\Layout\ContextManager;
use MakinaCorpus\Ucms\Layout\Item;

class RegionDropHandler extends AbstractNodeDropHandler
{
    private $manager;
    private $currentUser;

    /**
     * Default constructor
     *
     * @param ContextManager $manager
     */
    public function __construct(EntityManager $entityManager, ContextManager $manager)
    {
        parent::__construct($entityManager, null, 'teaser');

        $this->manager = $manager;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'region';
    }

    /**
     * {@inheritdoc}
     */
    public function accepts($id, DragItem $target)
    {
        return 'node' === $target->getType();
    }

    /**
     * {@inheritdoc}
     */
    public function drop($id, DragItem $target, $options = [])
    {
        /*
        $theme = $GLOBALS['theme'];

        if (!$this->manager->isRegionInEditMode($id)) {
            return new DropStatus(t("Region is not in edit mode"), true);
        }

        if ($this->manager->isPageContextRegion($id, $theme)) {
            $layout = $this->manager->getPageContext()->getCurrentLayout();
        } else if ($this->manager->isTransversalContextRegion($id, $theme)) {
            $layout = $this->manager->getSiteContext()->getCurrentLayout();
        }

        $layout
            ->getRegion($id)
            ->append(new Item($target->getId()))
        ;

        $context->getStorage()->save($layout);
         */

        return new DropStatus('', false, false, false);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($id, DragItem $target)
    {
        /*
        $this->storage->removeFor($this->currentUser->id(), $target->getId());
         */

        return new DropStatus('', false, false, false);
    }
}
