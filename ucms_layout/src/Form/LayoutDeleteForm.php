<?php

namespace MakinaCorpus\Ucms\Layout\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use MakinaCorpus\Ucms\Layout\Layout;
use MakinaCorpus\Ucms\Layout\StorageInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

class LayoutDeleteForm extends FormBase
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
        return 'ucms_layout_delete_form';
    }

    /**
     * {inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, Layout $layout = null)
    {
        $form['#layout_id'] = $layout->getId();
        $form['#layout_title'] = $layout->getTitle();

        $question = t("Delete");
        $description = t("Remove %page page ?", ['%page' => $layout->getAdminTitle()]);

        return confirm_form($form, $question, 'layout/' . $layout->getId(), $description);
    }

    /**
     * {inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $this->storage->delete($form['#layout_id']);

        drupal_set_message(t("Page %page has been deleted.", ['%page' => $form['#layout_title']]), 'error');

        // The layout does not exists anymore, we cannot return on the layout page.
        if (!empty($_GET['destination']) && false !== strpos($_GET['destination'], 'layout/')) {
            unset($_GET['destination']);
        }

        $form_state->setRedirect('admin/layout');
    }
}
