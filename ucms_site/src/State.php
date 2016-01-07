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
}
