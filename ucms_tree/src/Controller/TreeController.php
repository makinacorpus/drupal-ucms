<?php

namespace MakinaCorpus\Ucms\Tree\Controller;


use MakinaCorpus\Drupal\Sf\Controller;
use MakinaCorpus\Umenu\DrupalMenuStorage;

class TreeController extends Controller
{
    /**
     * @return DrupalMenuStorage
     */
    public function getStorage()
    {
        return $this->get('umenu.storage');
    }
}
