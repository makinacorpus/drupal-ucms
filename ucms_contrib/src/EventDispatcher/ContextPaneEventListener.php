<?php

namespace MakinaCorpus\Ucms\Contrib\EventDispatcher;

use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\EventDispatcher\ContextPaneEvent;

class ContextPaneEventListener
{
    use StringTranslationTrait;

    public function onUcmsdashboardContextinit(ContextPaneEvent $event)
    {
        // Add a backlink
        if (!path_is_admin(current_path())) {
            $backlink = new Action(t("Go to dashboard"), 'admin/dashboard', null, 'dashboard');
        } else {
            $backlink = new Action(t("Go to site"), '<front>', null, 'globe');
        }
        $contextPane = $event->getContextPane();
        $contextPane->addActions([$backlink]);

        // Add node creation link
        if (substr(current_path(), 0, 16) == 'admin/dashboard/') {
            $tab = arg(2);
            $actions = [];
            $types = node_type_get_names();
            $tab_types = variable_get('ucms_contrib_tab_'.$tab.'_type', []);
            foreach (array_values($tab_types) as $index => $type) {
                if (node_access('create', $type)) {
                    $actions [] = new Action(
                        $this->t('Create !content_type', ['!content_type' => $this->t($types[$type])]),
                        'node/add/'.strtr($type, '_', '-'),
                        null,
                        null,
                        $index,
                        !$index,
                        true);
                }
            }
            $contextPane->addActions($actions);
        }
    }

}
