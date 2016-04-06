<?php

namespace MakinaCorpus\Ucms\Notification\Formatter;

use MakinaCorpus\APubSub\Notification\NotificationInterface;
use MakinaCorpus\Drupal\APubSub\Notification\AbstractNotificationFormatter;
use MakinaCorpus\Ucms\Site\SiteManager;

class SiteRequest extends AbstractNotificationFormatter
{
    /**
     * @var SiteManager
     */
    private $manager;

    /**
     * Default constructor
     *
     * @param SiteManager $manager
     */
    public function __construct(SiteManager $manager)
    {
        $this->manager = $manager;
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
        foreach ($this->manager->getStorage()->loadAll($idList) as $site) {
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

    public function getTranslations()
    {
        $this->t("Site @title has been requested by @name");
        $this->t("Site @title have been requested by @name");
        $this->t("Site @title has been requested");
        $this->t("Site @title have been requested");
    }

    /**
     * {@inheritDoc}
     */
    protected function prepareImageURI(NotificationInterface $notification)
    {
        return "cloud";
    }
}
