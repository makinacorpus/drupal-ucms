<?php

namespace MakinaCorpus\Ucms\Label\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @todo this the wrong way of doing this, we actually should better export
 *   notification driven specifics into their own configuration file that
 *   should be loaded by the kernel only if the dependency exists
 */
class NotificationSupportCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('ucms_notification.service') && !$container->hasAlias('ucms_notification.service')) {
            return;
        }

        $container->addDefinitions([
            'ucms_label.label_notificiation_action_provider' =>
                (new Definition(
                    'MakinaCorpus\Ucms\Label\Action\LabelNotificationsActionProvider',
                    [
                        new Reference('ucms_label.manager'),
                        new Reference('current_user'),
                        new Reference('ucms_notification.service'),
                    ]
                ))
                ->addTag('ucms_dashboard.action_provider')
            ,
            'ucms_label.label_notificiation_action_provider' => (new Definition(
                (new Definition(
                    'MakinaCorpus\Ucms\Label\EventDispatcher\LabelEventListener',
                    [
                        new Reference('ucms_label.manager'),
                        new Reference('apb.notification'),
                    ]
                ))
                ->addTag('event_listener', ['event' => 'label:add'])
                ->addTag('event_listener', ['event' => 'label:edit'])
                ->addTag('event_listener', ['event' => 'label:delete'])
            ))
        ]);

        // Remove everything notification-related.
        if ($container->hasDefinition('ucms_label.label_event_listener')) {
            $container->removeDefinition('ucms_label.label_event_listener');
        }
        if ($container->hasAlias('ucms_label.label_event_listener')) {
            $container->removeAlias('ucms_label.label_event_listener');
        }

        if ($container->hasDefinition('ucms_label.label_notificiation_action_provider')) {
            $container->removeDefinition('ucms_label.label_notificiation_action_provider');
        }
        if ($container->hasAlias('ucms_label.label_notificiation_action_provider')) {
            $container->removeAlias('ucms_label.label_notificiation_action_provider');
        }
    }
}
