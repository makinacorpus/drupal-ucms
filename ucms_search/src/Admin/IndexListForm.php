<?php

namespace MakinaCorpus\Ucms\Search\Admin;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;

use MakinaCorpus\Ucms\Search\IndexStorage;

use Symfony\Component\DependencyInjection\ContainerInterface;

class IndexListForm extends FormBase
{
    /**
     * @var \DatabaseConnection
     */
    private $db;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var IndexStorage
     */
    private $indexStorage;

    /**
     * {inheritdoc}
     */
    static public function create(ContainerInterface $container)
    {
        return new self(
            $container->get('database'),
            $container->get('ucms_search.elastic.client'),
            $container->get('ucms_search.index_storage')
        );
    }

    /**
     * Default constructor
     *
     * @param \DatabaseConnection $db
     * @param Client $client
     */
    public function __construct(\DatabaseConnection $db, Client $client, IndexStorage $indexStorage)
    {
        $this->db = $db;
        $this->client = $client;
        $this->indexStorage = $indexStorage;
    }

    /**
     * {inheritdoc}
     */
    public function getFormId()
    {
        return 'ucms_search_index_list_form';
    }

    /**
     * Get rendered actions links for given index
     *
     * @param string $index
     *   Index key.
     *
     * @return array
     *   drupal_render() friendly structure.
     */
    private function getIndexOperations($index)
    {
        $links  = [];

        /*
        $base   = 'admin/config/search/indices/' . $index;
        $query  = drupal_get_destination();

        $links['mapping'] = [
          'href'  => $base,
          'title' => $this->t("Mapping"),
          'query' => $query,
        ];
         */

        return [
            '#theme' => 'links__ucms_search_index_actions',
            '#links' => $links
        ];
    }

    /**
     * FIXME move this elsewhere.
     *
     * Answer taken from https://stackoverflow.com/a/2510459 - all credits to its
     * original author from the http://php.net website.
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes  = max($bytes, 0);
        $pow    = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow    = min($pow, count($units) - 1);

        // Uncomment one of the following alternatives
        $bytes /= pow(1024, $pow);
        // $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * {inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $header = [
            $this->t("Id"),
            $this->t("Name"),
            $this->t("Total"),
            $this->t("Queued"),
            $this->t("Indexed"),
            $this->t("Size"),
            ''
        ];

        $indices  = $this->indexStorage->names();
        $stats    = null;

        $q = $this->db->select('ucms_search_status', 's');
        $q->fields('s', ['index_key']);
        $q->addExpression("COUNT(index_key)", 'count');
        $waiting = $q
            ->condition('s.needs_reindex', 0, '<>')
            ->groupBy('s.index_key')
            ->execute()
            ->fetchAllKeyed()
        ;
        $q = $this->db->select('ucms_search_status', 's');
        $q->fields('s', ['index_key']);
        $q->addExpression("COUNT(index_key)", 'count');
        $total = $q
            ->groupBy('s.index_key')
            ->execute()
            ->fetchAllKeyed()
        ;

        try {
            $stats = $this->client->indices()->status(['index' => implode(',', array_keys($indices))]);
        } catch (Missing404Exception $e) {
            watchdog_exception(__FUNCTION__, $e);
        }

        $rows = [];
        foreach ($indices as $index => $name) {

            $row = [
                check_plain($index),
                check_plain($name),
            ];

            $row[] = isset($total[$index]) ? number_format($total[$index]) : 0;
            $row[] = isset($waiting[$index]) ? number_format($waiting[$index]) : 0;

            if ($stats && $stats['indices'][$index]) {
                $row[] = number_format($stats['indices'][$index]['docs']['num_docs']);
                $row[] = $this->formatBytes($stats['indices'][$index]['index']['size_in_bytes']);
            } else {
                $row[] = $row[] = '<em>' . $this->t("Error") . '</em>';
            }

            $actions = $this->getIndexOperations($index);
            $row[] = drupal_render($actions);

            $rows[$index] = $row;
        }

        $form['indices'] = [
            '#type'     => 'tableselect',
            '#header'   => $header,
            '#options'  => $rows,
            '#required' => true,
        ];

        $form['actions']['#type'] = 'actions';
        $form['actions']['reindex'] = [
            '#type'   => 'submit',
            '#value'  => $this->t("Re-index selected"),
            '#submit' => ['::reindexSubmit'],
        ];
        $form['actions']['index'] = [
            '#type'   => 'submit',
            '#value'  => $this->t("Index missing"),
            '#submit' => ['::indexMissingSubmit'],
        ];

        return $form;
    }

    /**
     * Reindex submit
     */
    public function reindexSubmit(array &$form, FormStateInterface $form_state)
    {
        $operations = [];
        foreach ($form_state->getValue('indices') as $index => $value) {
            if ($value && $value === $index) {
                $operations[] = ['ucms_search_admin_reindex_batch_operation', [$index]];
            }
        }

        batch_set([
            'title'       => $this->t("Re-indexing"),
            'file'        => drupal_get_path('module', 'ucms_search') . '/ucms_search.admin.inc',
            'operations'  => $operations,
        ]);
    }

    /**
     *  Index missing submit
     */
    public function indexMissingSubmit(array &$form, FormStateInterface $form_state)
    {
        $operations = [];
        foreach ($form_state->getValue('indices') as $index => $value) {
            if ($value && $value === $index) {
                $operations[] = ['ucms_search_admin_index_batch_operation', [$index]];
            }
        }

        batch_set([
            'title'       => $this->t("Indexing missing documents"),
            'file'        => drupal_get_path('module', 'ucms_search') . '/ucms_search.admin.inc',
            'operations'  => $operations,
        ]);
    }

    /**
     * {inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        // Nothing to do here.
    }
}
