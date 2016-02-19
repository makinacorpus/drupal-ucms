<?php

namespace MakinaCorpus\Ucms\Contrib\Action;


use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\Action\ActionProviderInterface;

/**
 * Class ContentActionProvider
 * @package MakinaCorpus\Ucms\Contrib\Action
 */
class ContentActionProvider implements ActionProviderInterface
{
    use StringTranslationTrait;

    /**
     * {@inheritDoc}
     */
    public function getActions($item)
    {
        // Add node creation link
        $actions = [];
        $types = node_type_get_names();
        // @todo inject variable contents into a service
        //   and get rid of variable_get() here
        $tab_types = variable_get('ucms_contrib_tab_'.$item.'_type', []);
        foreach (array_values($tab_types) as $index => $type) {
            if (node_access('create', $type)) {
                $label = $this->t('Create !content_type', ['!content_type' => $this->t($types[$type])]);
                $actions [] = new Action($label, 'node/add/'.strtr($type, '_', '-'), null, null, $index, false, true);
            }
        }

        return $actions;
    }

    /**
     * {@inheritDoc}
     */
    public function supports($item)
    {
        return in_array($item, ['content', 'media']);
    }
}
