<?php

namespace MakinaCorpus\Ucms\Site\Action;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\ACL\Impl\Symfony\AuthorizationAwareInterface;
use MakinaCorpus\ACL\Impl\Symfony\AuthorizationAwareTrait;
use MakinaCorpus\Drupal\Dashboard\Action\ActionProviderInterface;

abstract class AbstractActionProvider implements ActionProviderInterface, AuthorizationAwareInterface
{
    use AuthorizationAwareTrait;
    use StringTranslationTrait;
}
