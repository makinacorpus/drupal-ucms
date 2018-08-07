<?php
use MakinaCorpus\Ucms\Search\Search;


/**
 * Implements hook_field_formatter_info()
 */
function ucms_search_field_formatter_info() {
  return [
    'ucms_search' => [
      'label'       => t('Search form and results'),
      'field types' => ['list_text'],
      'settings'    => [],
    ],
  ];
}

/**
 * Implements hook_field_formatter_view().
 */
function ucms_search_field_formatter_view($entity_type, $entity, $field, $instance, $langcode, $items, $display) {
  $element = [];
  $request = \Drupal::requestStack()->getCurrentRequest();

  /** @var \Drupal\Core\Form\FormBuilder $form_builder */
  $form_builder = Drupal::formBuilder();

  /** @var \MakinaCorpus\Ucms\Search\Formatter $formatter */
  $formatter = \Drupal::service('ucms_search.formatter');

  $element[0]['form'] = $form_builder->getForm('\MakinaCorpus\Ucms\Search\Form\SearchForm', $entity);

  // Harcoded node information, but we will use this field with nodes only.
  if (!arg(3) && ($current = menu_get_object()) && $current->nid === $entity->nid && $request->get(Search::PARAM_FULLTEXT_QUERY)) {
    $types = array_column($items, 'value');
    $element[0]['results'] = $formatter->render($entity, $request, $types);
  }

  return $element;
}

