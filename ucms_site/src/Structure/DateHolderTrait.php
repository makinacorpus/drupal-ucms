<?php

namespace MakinaCorpus\Ucms\Site\Structure;

trait DateHolderTrait
{
     /**
     * Initialize date from whatever value is given
     *
     * @param null|int|string|\DateTimeInterface $date
     *
     * @return \DateTimeInterface
     */
    protected function ensureDate($date): \DateTimeInterface
    {
        if (!$date) {
            return new \DateTime();
        }
        if ($date instanceof \DateTimeInterface) {
            return $date;
        }
        if (is_int($date)) {
            return new \DateTimeImmutable('@' . $date);
        }
        return new \DateTimeImmutable($date);
    }
}
