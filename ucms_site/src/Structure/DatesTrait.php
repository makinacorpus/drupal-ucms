<?php

namespace MakinaCorpus\Ucms\Site\Structure;

trait DatesTrait
{
    use DateHolderTrait;

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
     * Initialize dates, you must call this from the constructor even when the
     * object is loaded from PDO.
     */
    protected function initDates()
    {
        $this->ts_created = $this->ensureDate($this->ts_created);
        $this->ts_changed = $this->ensureDate($this->ts_changed);
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

    public function createdAt(): \DateTimeInterface
    {
        return $this->ts_created;
    }

    public function changedAt(): \DateTimeInterface
    {
        return $this->ts_changed;
    }
}
