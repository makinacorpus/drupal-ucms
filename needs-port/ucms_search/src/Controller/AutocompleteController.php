<?php

namespace MakinaCorpus\Ucms\Search\Controller;

use MakinaCorpus\Drupal\Sf\Controller;

class AutocompleteController extends Controller
{

    public function searchAction($text)
    {
        $suggester = $this->get('ucms_search.autocomplete');
        return $suggester->execute($text, 1);
    }
}
