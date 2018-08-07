<?php

namespace MakinaCorpus\Ucms\Taxo\Controller;

use MakinaCorpus\Drupal\Sf\Controller;

class TermController extends Controller
{
    /**
     * Main vocabulary list page
     *
     * @todo In master, convert this to calista
     */
    public function listVocabulariesAction()
    {
        /** @var \DatabaseConnection $database */
        $database = $this->get('database');

        $vocabularies = $database
            ->select('taxonomy_vocabulary', 'v')
            ->fields('v', ['vid', 'name', 'machine_name', 'description', 'hierarchy'])
            ->orderBy('v.weight')
            ->orderBy('v.name')
            ->orderBy('v.vid')
            ->addTag('ucms_vocabulary_access')
            ->execute()
        ;

        return $this->render('@ucms_taxo/views/term/vocabularies.html.twig', ['vocabularies' => $vocabularies]);
    }

    /**
     * List terms of a vocabulary page
     *
     * @todo In master, convert this to calista
     */
    public function listTermsAction($vocabulary)
    {
        /** @var \DatabaseConnection $database */
        $database = $this->get('database');

        $query = $database->select('taxonomy_term_data', 't');
        $query->fields('t', ['tid', 'vid', 'name', 'format', 'description', 'is_locked', 'user_id']);
        $query->join('taxonomy_vocabulary', 'v', "v.vid = t.vid");
        $query->leftJoin('taxonomy_term_hierarchy', 'h', "h.tid = t.tid");
        $query->leftJoin('taxonomy_term_data', 'p', "p.tid = h.parent");
        $query->addField('v', 'machine_name', 'vocabulary_machine_name');
        $query->addField('h', 'parent', 'parent');
        $query->addField('p', 'name', 'parent_name');
        $query->condition('t.vid', $vocabulary->vid);
        $terms = $query
            ->orderBy('p.weight')
            ->orderBy('t.weight')
            ->orderBy('t.name')
            ->orderBy('t.tid')
            ->addTag('ucms_term_access')
            ->execute()
            ->fetchAll()
        ;

        $map = [];
        foreach ($terms as $term) {
            if ($term->user_id) {
                $map[$term->user_id] = $term->user_id;
            }
        }

        return $this->render('@ucms_taxo/views/term/terms.html.twig', ['terms' => $terms, 'users' => user_load_multiple($map)]);
    }
}
