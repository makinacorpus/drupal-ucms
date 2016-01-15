<?php

namespace MakinaCorpus\Ucms\Notification;

use MakinaCorpus\APubSub\Notification\Formatter\AbstractFormatter;
use MakinaCorpus\APubSub\Notification\Notification;
use MakinaCorpus\Ucms\Site\SiteFinder;
use MakinaCorpus\Ucms\Site\State;

class SiteStateChanged extends AbstractFormatter
{
    /**
     * @var SiteFinder
     */
    private $siteFinder;

    /**
     * Default constructor
     *
     * @param SiteFinder $siteFinder
     */
    public function __construct($type, SiteFinder $siteFinder)
    {
        parent::__construct($type, "Site admin", null, false);

        $this->siteFinder = $siteFinder;
    }

    /**
     * {@inheritdoc}
     */
    public function format(Notification $notification)
    {
        $states = State::getList();

        try {
            $title = $this->siteFinder->findOne($notification['id'])->title;
        } catch (\InvalidArgumentException $e) {
            $title = t("unknown");
        }

        if (isset($notification['uid']) && ($account = user_load($notification['uid']))) {
            $name = format_username($account);
        } else {
            $name = t("someone");
        }

        $state = $notification['state'];
        switch ($state) {

            case State::REQUESTED:
                return t("New site request by %account: %title", [
                    '%account'  => $name,
                    '%title'    => $title,
                ]);

            default:
                if (isset($states[$state])) {
                    $what = $states[$state];
                } else {
                    $what = t("unknown status");
                }

                return t("%account changed the site %title state to %state", [
                    '%account'  => $name,
                    '%title'    => $title,
                    '%state'    => $what,
                ]);
        }
    }
}
