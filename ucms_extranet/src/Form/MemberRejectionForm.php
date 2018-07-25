<?php


namespace MakinaCorpus\Ucms\Extranet\Form;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\UserInterface;

use MakinaCorpus\Ucms\Extranet\EventDispatcher\ExtranetMemberEvent;
use MakinaCorpus\Ucms\Site\Site;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;


/**
 * Form to reject extranet member registrations.
 */
class MemberRejectionForm extends FormBase
{
    /**
     * {@inheritdoc}
     */
    static public function create(ContainerInterface $container)
    {
        return new self(
            $container->get('entity.manager'),
            $container->get('event_dispatcher')
        );
    }


    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;


    public function __construct(EntityManager $entityManager, EventDispatcherInterface $dispatcher)
    {
        $this->entityManager = $entityManager;
        $this->dispatcher = $dispatcher;
    }


    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'ucms_extranet_member_rejection_form';
    }


    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $formState, Site $site = null, UserInterface $account = null)
    {
        if (null === $site || null === $account) {
            return [];
        }

        $formState->setTemporaryValue('site', $site);
        $formState->setTemporaryValue('account', $account);

        return confirm_form(
            $form,
            $this->t("Reject the registration of !name?", ['!name' => $account->getDisplayName()]),
            'admin/dashboard/site/' . $site->getId() . '/webmaster',
            ''
        );
    }


    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $formState)
    {
        /** @var $site Site */
        $site = $formState->getTemporaryValue('site');
        /** @var $account UserInterface */
        $account = $formState->getTemporaryValue('account');

        user_cancel([], $account->id(), 'user_cancel_reassign');
        // $this->entityManager->getStorage('user')->delete([$account]);

        drupal_set_message($this->t("Registration of !name has been rejected.", ['!name' => $account->getDisplayName()]));

        $event = new ExtranetMemberEvent($account, $site, ['name' => $account->getDisplayName()]);
        $this->dispatcher->dispatch(ExtranetMemberEvent::EVENT_REJECT, $event);
    }
}



