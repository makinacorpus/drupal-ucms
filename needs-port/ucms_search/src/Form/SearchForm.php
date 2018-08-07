<?php

namespace MakinaCorpus\Ucms\Search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;
use MakinaCorpus\Ucms\Search\Search;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Public form used in search component formatter.
 *
 * @package MakinaCorpus\Ucms\Search\Form
 */
class SearchForm extends FormBase
{
    static public function create(ContainerInterface $container)
    {
        return new self(
            $container->get('request_stack')
        );
    }

    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * {inheritdoc}
     */
    public function getFormId()
    {
        return 'ucms_search_form';
    }

    /**
     * {inheritdoc}
     *
     * @param array $form
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     * @param null|NodeInterface $node
     * @return array
     */
    public function buildForm(array $form, FormStateInterface $form_state, $node = null)
    {
        $request = $this->requestStack->getCurrentRequest();

        $form['#method'] = 'get';
        $form['#action'] = url('node/'.$node->nid);
        $form['#after_build'][] = '::formAfterBuild';
        $form['#token'] = false;
        $form['#attributes'] = ['class' => [drupal_html_class($this->getFormId())]];

        $form[Search::PARAM_FULLTEXT_QUERY] = [
            '#type'          => 'textfield',
            '#default_value' => $request->query->get(Search::PARAM_FULLTEXT_QUERY),
            '#prefix'        => '<div class="input-group">',
            '#attributes'    => [
                'class'       => [],
                'placeholder' => $this->t("Specify your keywords"),
            ],
        ];

        // No need to use an input submit with GET method. Furthermore, it won't be
        // exposed as GET parameter with an input button.
        $form['submit'] = [
            '#type'    => 'button',
            '#content' => '<span class="glyphicon glyphicon-search"></span>',
            '#prefix'  => '<span class="input-group-btn">',
            '#suffix'  => '</span></div>',
        ];

        return $form;
    }

    /**
     * After build callback on the form element to remove form build ID.
     * It allow us to cache per page.
     */
    public function formAfterBuild(array $form, FormStateInterface $form_state)
    {
        unset($form['form_build_id']);
        unset($form['form_id']);

        return $form;
    }

    /**
     * {@inheritDoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
    }
}
