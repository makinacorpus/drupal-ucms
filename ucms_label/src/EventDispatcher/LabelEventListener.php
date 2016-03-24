<?php


namespace MakinaCorpus\Ucms\Label\EventDispatcher;

use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\APubSub\Notification\EventDispatcher\ResourceEvent;
use MakinaCorpus\APubSub\Notification\NotificationService;
use MakinaCorpus\Ucms\Label\LabelManager;


class LabelEventListener
{
    use StringTranslationTrait;


    /**
     * @var LabelManager
     */
    protected $labelManager;

    /**
     * @var NotificationService
     */
    protected $notifService;


    /**
     * Constructor
     *
     * @param LabelManager $labelManager
     * @param NotificationService $notifService
     */
    public function __construct(LabelManager $labelManager, NotificationService $notifService)
    {
        $this->labelManager = $labelManager;
        $this->notifService = $notifService;
    }


    /**
     * label:add events handler method.
     *
     * @param ResourceEvent $event
     */
    public function onLabelAdd(ResourceEvent $event)
    {
        $labelIdList  = $event->getResourceIdList();
        $labels       = $this->labelManager->loadLabels($labelIdList);
        $channels     = [];

        foreach ($labels as $label) {
            if (!$this->labelManager->isRootLabel($label)) {
                $channels[] = 'label:' . $label->tid;
            }
        }

        if (!empty($channels)) {
            $this->notifService->getBackend()->createChannels($channels);
        }
    }


    /**
     * label:edit events handler method.
     *
     * @param ResourceEvent $event
     */
    public function onLabelEdit(ResourceEvent $event)
    {
        $labelIdList    = $event->getResourceIdList();
        $labels         = $this->labelManager->loadLabels($labelIdList);
        $channelsToAdd  = [];
        $channelsToDel  = [];

        foreach ($labels as $label) {
            if (!$this->labelManager->isRootLabel($label)) {
                $channelsToAdd[] = 'label:' . $label->tid;
            } else {
                $channelsToDel[] = 'label:' . $label->tid;
            }
        }

        if (!empty($channelsToAdd)) {
            $this->notifService->getBackend()->createChannels($channelsToAdd, true);
        }
        if (!empty($channelsToDel)) {
            $this->notifService->getBackend()->deleteChannels($channelsToDel, true);
        }
    }


    /**
     * label:delete events handler method.
     *
     * @param ResourceEvent $event
     */
    public function onLabelDelete(ResourceEvent $event)
    {
        $labelIdList  = $event->getResourceIdList();
        $channels     = [];

        foreach ($labelIdList as $labelId) {
            $channels[] = 'label:' . $labelId;
        }
        
        $this->notifService->getBackend()->deleteChannels($channels, true);
    }
}


