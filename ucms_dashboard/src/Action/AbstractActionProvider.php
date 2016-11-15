<?php

namespace MakinaCorpus\Ucms\Dashboard\Action;

use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\ACL\Impl\Symfony\AuthorizationAwareInterface;
use MakinaCorpus\ACL\Impl\Symfony\AuthorizationAwareTrait;

abstract class AbstractActionProvider implements ActionProviderInterface, AuthorizationAwareInterface
{
    use AuthorizationAwareTrait;
    use StringTranslationTrait;
}
