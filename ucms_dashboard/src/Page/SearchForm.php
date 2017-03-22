<?php

namespace MakinaCorpus\Ucms\Dashboard\Page;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class SearchForm extends FormBase
{
    /**
     * Default GET parameter name
     */
    const DEFAULT_PARAM_NAME = 's';

    /* private */ const TEMP_PARAM = 'parameterName';

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'ucms_dashboard_search_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $query = null, $parameterName = null)
    {
        if (!$parameterName) {
            $parameterName = self::DEFAULT_PARAM_NAME;
        }

        // Strip the s= & q= parameters from current query.
        $parameters = drupal_get_query_parameters(null, ['q', $parameterName]);
        $form['#action'] = url(current_path(), ['query' => $parameters]);

        $form_state
            ->setCached(false)
            ->setTemporaryValue(self::TEMP_PARAM, $parameterName)
        ;

        // @todo
        //  $query is not propagated to submitForm() because it uses
        //  drupal_get_query_parameters(), we should find a better way
        if (!$query) {
            $query = $_GET;
        }

        $form['query'] = [
            '#type'           => 'textfield',
            '#attributes'     => ['placeholder' => $this->t("Search")],
            '#default_value'  => isset($query[$parameterName]) ? $query[$parameterName] : null,
        ];

        $form['clear'] = [
            '#type'   => 'submit',
            '#value'  => $this->t("Clear"),
            '#submit' => ['::clearSubmit'],
            '#access' => !empty($form['query']['#default_value']) || !empty($_POST),
        ];

        $form['submit'] = [
            '#type'   => 'submit',
            '#value'  => $this->t("Search"),
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function clearSubmit(array &$form, FormStateInterface $form_state)
    {
        $parameterName = $form_state->getTemporaryValue(self::TEMP_PARAM);

        $query = drupal_get_query_parameters(null, ['q', $parameterName]);

        $form_state->setRedirect(current_path(), ['query' => $query]);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $parameterName  = $form_state->getTemporaryValue(self::TEMP_PARAM);
        $searchString   = $form_state->getValue('query');

        if ($searchString) {
            $query = [$parameterName => $searchString] + drupal_get_query_parameters();
        } else {
            $query = drupal_get_query_parameters(null, ['q', $parameterName]);
        }

        $form_state->setRedirect(current_path(), ['query' => $query]);
    }
}
