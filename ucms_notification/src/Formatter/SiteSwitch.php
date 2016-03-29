<?php

namespace MakinaCorpus\Ucms\Notification\Formatter;

use MakinaCorpus\APubSub\Notification\NotificationInterface;
use MakinaCorpus\Drupal\APubSub\Notification\AbstractNotificationFormatter;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\Site\SiteState;

class SiteSwitch extends AbstractNotificationFormatter
{
    /**
     * @var SiteManager
     */
    private $manager;

    /**
     * Default constructor
     *
     * @param SiteManager $storage
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
        $list = SiteState::getList();

        if (isset($notification['from'])) {
            $args['@from']  = $list[$notification['from']];
            $args['@to']    = $list[$notification['to']];
        } else {
            // Someone forgot to set data on their resource event!
            $args['@from']  = '?';
            $args['@to']    = '?';
        }

        if ($name = $this->getUserAccountName($notification)) {
            $args['@name'] = $name;
            return [
                "@name switched @title from @from to @to",
                "@name switched @title from @from to @to",
            ];
        } else {
            return [
                "@title switched from @from to @to",
                "@title switched from @from to @to",
            ];
        }
    }

    public function getTranslations()
    {
        $this->t("@name switched @title from @from to @to");
        $this->t("@title switched from @from to @to");
    }

    /**
     * {@inheritDoc}
     */
    public function getImageURI(NotificationInterface $notification)
    {
        return "cloud";
    }
}
