<?php

namespace MakinaCorpus\Ucms\ContentList;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\Page\PageState;
use MakinaCorpus\Ucms\Site\Site;

/**
 * In most cases, all content list should extend this
 */
abstract class AbstractContentList implements ContentListInterface
{
    use StringTranslationTrait;

    private $entityManager;

    /**
     * Default construtor
     *
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Get entity manager
     *
     * @return EntityManager
     */
    final protected function getEntityManager()
    {
        return $this->entityManager;
    }

    public function render(EntityInterface $entity, Site $site, $options = [], $formatterOptions = [])
    {
        $pageState = new PageState();
        $pageState->setRange($formatterOptions['limit']);
        $pageState->setPagerElement(++\PagerDefault::$maxElement);
        $pageState->setSortField($formatterOptions['order_field']);
        $pageState->setSortOrder($formatterOptions['order']);

        $idList = $this->fetch($entity, $site, $pageState, $options);

        if (!empty($idList)) {
            $nodes = $this->entityManager->getStorage('node')->loadMultiple($idList);

            $ret = [
              '#theme'      => 'ucms_list',
              '#nodes'      => $nodes,
              '#view_mode'  => $formatterOptions['view_mode'],
              '#pager'      => empty($formatterOptions['use_pager']) ? null : ['#theme' => 'pager', '#element' => $pageState->getPagerElement()],
              '#limit'      => $pageState->getLimit(),
              '#count'      => count($nodes),
            ];
          } else {
              $ret = ['#markup' => '<p class="text-center">Pas de contenu pour le moment</p>'];
          }

          return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOptions()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getOptionsForm($options = [])
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultFormatterOptions()
    {
        return [
            'view_mode'   => 'default',
            'limit'       => 3,
            'pager'       => false,
            'order'       => 'desc',
            'order_field' => 'created',
        ];
    }

    /**
     * Get view mode list
     *
     * @return string[]
     */
    private function getViewModeList()
    {
        $ret = [];
        $entityInfo = entity_get_info('node');

        foreach ($entityInfo['view modes'] as $viewMode => $info) {
            $ret[$viewMode] = $info['label'];
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormatterOptionsForm($options = [])
    {
        $form = [];

        $form['view_mode'] = [
            '#type'           => 'options',
            '#options'        => $this->getViewModeList(),
            '#title'          => $this->t("View mode"),
            '#default_value'  => $options['view_mode'],
            '#required'       => true,
        ];

        $form['limit'] = [
            '#type'           => 'select',
            '#title'          => $this->t("Number of items to display"),
            '#options'        => drupal_map_assoc(range(1, 50)),
            '#default_value'  => $options['limit'],
            '#required'       => true,
        ];
        $form['pager'] = [
            '#type'           => 'checkbox',
            '#title'          => $this->t("Use pager"),
            '#default_value'  => $options['pager'],
            '#required'       => true,
        ];

        $form['order'] = [
            '#type'           => 'select',
            '#options'        => ['asc' => $this->t("Ascending"), 'desc' => $this->t("Descending")],
            '#title'          => $this->t("Order"),
            '#default_value'  => $options['order'],
            '#required'       => true,
        ];
        $form['order_field'] = [
            '#type'           => 'textfield',
            '#title'          => $this->t("Order field"),
            '#default_value'  => $options['order_field'],
            '#required'       => true,
        ];

        return $form;
    }
}
