<?php

namespace MakinaCorpus\Ucms\Widget\Impl;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\Widget\WidgetInterface;
use MakinaCorpus\Umenu\TreeManager;

/**
 * Display a menu where you want it to be
 */
class MenuWidget implements WidgetInterface
{
    use StringTranslationTrait;

    private $treeManager;
    private $siteManager;

    public function __construct(TreeManager $treeManager, SiteManager $siteManager)
    {
        $this->treeManager = $treeManager;
        $this->siteManager = $siteManager;
    }

    /**
     * {@inheritdoc}
     */
    public function render(EntityInterface $entity, Site $site, $options = [], $formatterOptions = [])
    {
        if ($options['name']) {
            try {
                $tree = $this->treeManager->buildTree($options['name']);

                if ($formatterOptions['suggestion']) {
                    $themeHook = 'umenu__' . $formatterOptions['suggestion'];
                } else {
                    $themeHook = 'umenu';
                }

                $current = null;
                if ($node = menu_get_object()) { // FIXME
                    $current = $node->nid;
                }

                return [
                    '#theme'    => $themeHook,
                    '#tree'     => $tree,
                    '#current'  => $current,
                ];

            } catch (\Exception $e) {
                // Be silent about this, we are rendering the front page
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOptions()
    {
        return ['name' => null];
    }

    /**
     * {@inheritdoc}
     */
    public function getOptionsForm($options = [])
    {
        $form = [];

        $select = [];
        if ($this->siteManager->hasContext()) {

            $menuList = $this->treeManager->getMenuStorage()->loadWithConditions([
                'site_id' => $this->siteManager->getContext()->getId(),
            ]);

            foreach ($menuList as $menu) {
                $select[$menu->getName()] = $menu->getTitle();
            }
        }

        $form['name'] = [
            '#type'           => 'select',
            '#title'          => $this->t("Menu name"),
            '#options'        => $select,
            '#empty_option'   => $select ? $this->t("Select a menu") : $this->t("You must be in a site context, which has menu, to be able to select one"),
            '#default_value'  => $options['name'],
            '#required'       => false,
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultFormatterOptions()
    {
        return [
            'suggestion' => 'node',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormatterOptionsForm($options = [])
    {
        $form = [];

        $form['suggestion'] = [
            '#type'           => 'textfield',
            '#title'          => $this->t("Template suggestion suffix"),
            '#default_value'  => $options['suggestion'],
            '#required'       => false,
        ];

        return $form;
    }
}
