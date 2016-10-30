<?php

namespace MakinaCorpus\Ucms\HttpCache\EventDispatcher;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

class MayCacheEvent extends Event
{
    private $request;

    public function __construct(Request $request)
    {

    }
}
