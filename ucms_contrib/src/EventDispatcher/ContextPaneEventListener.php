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
        // Add the shopping cart
        if (user_access('use favorites')) {
            // On admin lists, on content creation or on layout edit
            $router_item = menu_get_item();
            $allowed_routes = [
                'node/%/edit',
                'admin/dashboard/content',
                'admin/dashboard/media',
            ];
            if (in_array($router_item['path'], $allowed_routes) || ucms_layout_context_get()->isTemporary()) {
                ucms_dashboard_context_get()
                    ->addTab('cart', t("Cart"), 'shopping-cart')
                    ->add(ucms_contrib_favorite_render(), 'cart');
            }
        }

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
