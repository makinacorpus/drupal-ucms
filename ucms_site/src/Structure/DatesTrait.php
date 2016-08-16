<?php

namespace MakinaCorpus\Ucms\Site\Structure;

trait DatesTrait
{
    /**
     * @todo Change this to protected once Site class is converted
     *
     * @var \DateTimeInterface
     */
    public $ts_created;

    /**
     * @todo Change this to protected once Site class is converted
     *
     * @var \DateTimeInterface
     */
    public $ts_changed;

    /**
     * This constructor should only be called by PDO, yet it is safe to use
     */
    public function __construct()
    {
        $this->initDates();
    }

    /**
     * Initialize date from whatever value is given
     *
     * @param null|int|string|\DateTimeInterface $date
     *
     * @return \DateTimeInterface
     */
    protected function initDate($date)
    {
        if (!$date) {
            return new \DateTime();
        }
        if ($date instanceof \DateTimeInterface) {
            return $date;
        }
        if (is_int($date)) {
            return new \DateTime('@' . $date);
        }
        return new \DateTime($date);
    }

    /**
     * Initialize dates, you must call this from the constructor even when the
     * object is loaded from PDO.
     */
    protected function initDates()
    {
        $this->ts_created = $this->initDate($this->ts_created);
        $this->ts_changed = $this->initDate($this->ts_changed);
    }

    public function createdAt()
    {
        return $this->ts_created;
    }

    /**
     * Change the changed date!
     *
     * @return \DateTimeInterface
     */
    public function touch()
    {
        return $this->ts_changed = new \DateTime();
    }

    public function changedAt()
    {
        return $this->ts_changed;
    }
}
