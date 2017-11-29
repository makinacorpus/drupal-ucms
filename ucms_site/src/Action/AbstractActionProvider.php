<?php

namespace MakinaCorpus\Ucms\Site\Action;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\ACL\Bridge\Symfony\AuthorizationAwareInterface;
use MakinaCorpus\ACL\Bridge\Symfony\AuthorizationAwareTrait;
use MakinaCorpus\Calista\Action\ActionProviderInterface;

abstract class AbstractActionProvider implements ActionProviderInterface, AuthorizationAwareInterface
{
    use AuthorizationAwareTrait;
    use StringTranslationTrait;
}
