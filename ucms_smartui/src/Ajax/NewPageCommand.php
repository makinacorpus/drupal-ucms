<?php

namespace MakinaCorpus\Ucms\SmartUI\Ajax;

use Drupal\Core\Ajax\CommandInterface;

class NewPageCommand implements CommandInterface
{
    private $uri;

    public function __construct($uri)
    {
        $this->uri = $uri;
    }

    public function render()
    {
        return [
            'command' => 'newPage',
            'method'  => 'replaceWith',
            'uri'     => url($this->uri),
        ];
    }
}
