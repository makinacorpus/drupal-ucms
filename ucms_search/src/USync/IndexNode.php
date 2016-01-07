<?php

namespace MakinaCorpus\Ucms\Search\USync;

use USync\AST\Drupal\DrupalNodeInterface;
use USync\AST\Drupal\DrupalNodeTrait;
use USync\AST\ValueNode;

class IndexNode extends ValueNode implements DrupalNodeInterface
{
    use DrupalNodeTrait;
}
