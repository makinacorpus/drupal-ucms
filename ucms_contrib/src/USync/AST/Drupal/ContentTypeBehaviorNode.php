<?php

namespace MakinaCorpus\Ucms\Contrib\USync\AST\Drupal;

use USync\AST\Drupal\DrupalNodeInterface;
use USync\AST\Drupal\DrupalNodeTrait;
use USync\AST\Node;

class ContentTypeBehaviorNode extends Node implements DrupalNodeInterface
{
    use DrupalNodeTrait;
}
