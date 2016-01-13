<?php

namespace MakinaCorpus\Ucms\Layout\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use MakinaCorpus\Ucms\Layout\Layout;
use MakinaCorpus\Ucms\Layout\StorageInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

class LayoutAddForm extends FormBase
{
    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * {inheritdoc}
     */
    static public function create(ContainerInterface $container)
    {
        return new self($container->get('ucms_layout.storage'));
    }

    /**
     * Default constructor
     *
     * @param StorageInterface $storage
     */
    public function __construct(StorageInterface $storage)
    {
        $this->storage = $storage;
    }

    /**
     * {inheritdoc}
     */
    public function getFormId()
    {
        return 'ucms_layout_add_form';
    }

    /**
     * {inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, Layout $layout = null)
    {
        $form['title'] = [
            '#title'          => t("Page title"),
            '#type'           => 'textfield',
            '#required'       => true,
            '#description'    => t("The page title that will be displayed on frontend."),
        ];

        $form['title_admin'] = [
            '#title'          => t("Title for administration"),
            '#type'           => 'textfield',
            '#required'       => true,
            '#description'    => t("This title will used for administrative pages and will never be shown to end users."),
        ];

        if (module_exists('path')) {
            // @todo
        }

        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = [
            '#type'   => 'submit',
            '#value'  => t("Create"),
        ];

        return $form;
    }

    /**
     * {inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $layout = (new Layout())
            ->setAccountId($this->currentUser()->uid)
            ->setAdminTitle($form_state->getValue('title_admin'))
            ->setTitle($form_state->getValue('title'))
            ->setSiteId(0) // @todo
        ;

        $this->storage->save($layout);

        drupal_set_message(t("Page %page has been created.", ['%page' => $layout->getAdminTitle()]));

        $form_state->setRedirect('layout/' . $layout->getId());
    }
}
