<?php

namespace MakinaCorpus\Ucms\ContentList\Impl;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManager;

use MakinaCorpus\Ucms\ContentList\AbstractContentList;
use MakinaCorpus\Ucms\Contrib\TypeHandler;
use MakinaCorpus\Ucms\Dashboard\Page\PageState;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Label\LabelManager;

/**
 * List content using content type
 */
class TypeContentListWidget extends AbstractContentList
{
    private $typeHandler;
    private $labelManager;

    public function __construct(EntityManager $entityManager, TypeHandler $typeHandler, LabelManager $labelManager = null)
    {
        parent::__construct($entityManager);

        $this->typeHandler = $typeHandler;
        $this->labelManager = $labelManager;
    }

    /**
     * {@inheritdoc}
     */
    public function fetch(EntityInterface $entity, Site $site, PageState $pageState, $options = [])
    {
          $typeList = $options['type'];

          if (empty($typeList)) {
              return [];
          }

          $exclude    = [];
          $limit      = $pageState->getLimit();
          // @todo fixme
          $usePager   = false ; //$pageState->

          // Exclude current node whenever it matches the conditions
          if (($current = menu_get_object()) && in_array($current->bundle(), $typeList)) {
              $exclude[] = $current->id();
          }

          $query = db_select('node', 'n');
          $query->join('ucms_site_node', 'un', 'un.nid = n.nid');

          if ($exclude) {
              $query->condition('n.nid', $exclude, 'NOT IN');
          }

          if ($options['tags']) {
              $query->groupBy('n.nid');
              $query->join('taxonomy_index', 'i', 'i.nid = n.nid');
              $query->condition('i.tid', $options['tags']);
          }

          $query
              ->fields('n', ['nid'])
              ->condition('n.type', $typeList)
              ->condition('n.status', NODE_PUBLISHED)
              ->condition('un.site_id', $site->getId())
              ->orderBy('n.' . $pageState->getSortField(), $pageState->getSortOrder())
              ->addMetaData('entity', $entity)
              ->addMetaData('ucms_list', $typeList)
              ->addTag('node_access')
          ;

          if ($usePager) {
              // execute() method must run on the query extender and not the
              // original query itself, do not change this
              $query = $query->extend('PagerDefault');
              $query->limit($limit);
          } else {
              $query->range(0, $limit);
          }

          return $query->execute()->fetchCol();
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOptions()
    {
        return [
            'type' => [],
            'tags' => [],
        ];
    }

    /**
     * Get allowed content types.
     */
    protected function getContentTypeList()
    {
        return $this->typeHandler->getTypesAsHumanReadableList($this->typeHandler->getEditorialContentTypes());
    }

    /**
     * Get allowed tags list.
     */
    protected function getContentTagsList()
    {
        $ret = [];

        if (!$this->labelManager) {
            return;
        }

        $tagList = $this->labelManager->loadAllLabels();

        if ($tagList) {
            foreach ($tagList as $tag) {
                $ret[$tag->tid] = $tag->name;
            }
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptionsForm($options = [])
    {
        $ret = parent::getOptionsForm($options);

        // @todo sorry for this, should be the theme responsability
        drupal_add_library('ucms_label', 'select2');
        drupal_add_js("jQuery(function(){jQuery('select').select2({theme:'bootstrap'})});", 'inline');
//         $ret['#attached']['library'][] = ['ucms_label', 'select2'];
//         $ret['#attached']['js'][] = [
//           'data' => "jQuery(function(){jQuery('select').select2({theme:'bootstrap'})})",
//           'type' => 'inline'
//         ];

        $ret = [
            'type' => [
                '#type'             => 'select',
                '#title'            => $this->t("Content types to display"),
                '#options'          => $this->getContentTypeList(),
                '#default_value'    => $options['type'],
                '#element_validate' => ['ucms_list_element_validate_filter'],
                '#multiple'         => true,
            ]
        ];

        $tagOptions = $this->getContentTagsList();
        if ($tagOptions) {
            $ret['tags'] = [
                '#type'             => 'select',
                '#title'            => $this->t("Tags for which content should be displayed"),
                '#options'          => $tagOptions,
                '#default_value'    => $options['tags'],
                '#element_validate' => ['ucms_list_element_validate_filter'],
                '#multiple'         => true,
            ];
        }

        return $ret;
    }
}
