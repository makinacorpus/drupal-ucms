<?php

namespace MakinaCorpus\Ucms\Notification\Formatter;

use MakinaCorpus\APubSub\Notification\NotificationInterface;
use MakinaCorpus\Drupal\APubSub\Notification\AbstractNotificationFormatter;
use MakinaCorpus\Ucms\Site\SiteStorage;

class SiteRequest extends AbstractNotificationFormatter
{
    /**
     * @var SiteStorage
     */
    private $storage;

    /**
     * Default constructor
     *
     * @param SiteStorage $storage
     */
    public function __construct(SiteStorage $storage)
    {
        $this->storage = $storage;
    }

    /**
     * {@inheritdoc}
     */
    public function getURI(NotificationInterface $interface)
    {
        $siteIdList = $interface->getResourceIdList();
        if (count($siteIdList) === 1) {
            return 'admin/dashboard/site/' . reset($siteIdList);
        }
    }

    /**
     * {inheritdoc}
     */
    protected function getTitles($idList)
    {
        $ret = [];
        foreach ($this->storage->loadAll($idList) as $site) {
            $ret[$site->id] = $site->title;
        }
        return $ret;
    }

    /**
     * {inheritdoc}
     */
    protected function getTypeLabelVariations($count)
    {
        return ["@count site", "@count sites"];
    }

    /**
     * {inheritdoc}
     */
    protected function getVariations(NotificationInterface $notification, array &$args = [])
    {
        if ($name = $this->getUserAccountName($notification)) {
            $args['@name'] = $name;
            return [
                "Site @title has been requested by @name",
                "Site @title have been requested by @name",
            ];
        } else {
            return [
                "Site @title has been requested",
                "Site @title have been requested",
            ];
        }
    }
}
