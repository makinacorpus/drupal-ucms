<?php

namespace MakinaCorpus\Ucms\Dashboard\Controller;

use Drupal\Core\Controller\ControllerBase;
use MakinaCorpus\Ucms\Dashboard\Action\ActionRegistry;
use MakinaCorpus\Ucms\Dashboard\Action\ItemIdentity;
use MakinaCorpus\Ucms\Dashboard\Action\Impl\ProcessAction;
use MakinaCorpus\Ucms\Dashboard\Form\ProcessActionConfirmForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ActionController extends ControllerBase
{
    private $actionRegistry;

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new self($container->get('ucms_dashboard.action_provider_registry'));
    }

    /**
     * Default constructor
     */
    public function __construct(ActionRegistry $actionRegistry)
    {
        $this->actionRegistry = $actionRegistry;
    }

    /**
     * Process an action
     */
    public function process(string $action, string $type, string $id)
    {
        $item = $this->actionRegistry->load(new ItemIdentity($type, $id));
        $actions = $this->actionRegistry->getActions($item);

        if (!isset($actions[$action]) || !$actions[$action] instanceof ProcessAction) {
            throw new NotFoundHttpException();
        }
        if (!$actions[$action]->isGranted()) {
            throw new AccessDeniedHttpException();
        }

        return $this->formBuilder()->getForm(ProcessActionConfirmForm::class, $actions[$action]);
    }
}
