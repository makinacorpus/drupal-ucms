<?php


namespace MakinaCorpus\Ucms\Label\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use MakinaCorpus\APubSub\Notification\EventDispatcher\ResourceEvent;
use MakinaCorpus\Ucms\Label\LabelManager;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;


/**
 * Label creation and edition form
 */
class LabelDelete extends FormBase
{

    /**
     * {@inheritdoc}
     */
    static public function create(ContainerInterface $container)
    {
        return new self(
            $container->get('ucms_label.manager'),
            $container->get('event_dispatcher')
        );
    }


    /**
     * @var LabelManager
     */
    protected $manager;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;


    public function __construct(LabelManager $manager, EventDispatcherInterface $dispatcher)
    {
        $this->manager = $manager;
        $this->dispatcher = $dispatcher;
    }


    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'ucms_label_delete';
    }


    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, \stdClass $label = null)
    {
        if ($label === null) {
            return [];
        }

        $form_state->setTemporaryValue('label', $label);
        $question = $this->t("Do you really want to delete the \"@name\" label?", ['@name' => $label->name]);
        return confirm_form($form, $question, 'admin/dashboard/label');
    }


    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        try {
            $label = $form_state->getTemporaryValue('label');
            $this->manager->deleteLabel($label);
            drupal_set_message($this->t("\"@name\" label has been deleted.", array('@name' => $label->name)));
        }
        catch (\Exception $e) {
            drupal_set_message($this->t("An error occured during the deletion of the \"@name\" label. Please try again.", array('@name' => $label->name)), 'error');
        }
    }

}
