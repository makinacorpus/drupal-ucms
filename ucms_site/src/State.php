<?php

namespace MakinaCorpus\Ucms\Site;

/**
 * State enumeration.
 */
final class State
{
    /**
     * Site is a pending request.
     */
    const REQUESTED = 0;

    /**
     * Site request is rejected, needs fixing.
     */
    const REJECTED = 10;

    /**
     * Site is pending creation.
     */
    const PENDING = 20;

    /**
     * Site has been created, needs content.
     */
    const INIT = 100;

    /**
     * Site is offline.
     */
    const OFF = 200;

    /**
     * Site is online.
     */
    const ON = 210;

    /**
     * Site is archive.
     */
    const ARCHIVE = 300;

    /**
     * Get a list of values as human readable values in english
     *
     * @return string[]
     *   Values are states as integers, values are human readable names
     *   in english, ready for translation
     */
    static public function getList()
    {
        return [
            self::REJECTED  => "Rejected",
            self::REJECTED  => "Requested",
            self::PENDING   => "Creation",
            self::INIT      => "Initialization",
            self::OFF       => "Off",
            self::ON        => "On",
            self::ARCHIVE   => "Archive",
        ];
    }
}
