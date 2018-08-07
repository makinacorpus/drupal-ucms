<?php


namespace MakinaCorpus\Ucms\Extranet\Form;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\UserInterface;

use MakinaCorpus\Ucms\Extranet\EventDispatcher\ExtranetMemberEvent;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\User\TokenManager;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;


/**
 * Form to accept extranet member registrations.
 */
class MemberAcceptanceForm extends FormBase
{
    /**
     * {@inheritdoc}
     */
    static public function create(ContainerInterface $container)
    {
        return new self(
            $container->get('entity.manager'),
            $container->get('ucms_user.token_manager'),
            $container->get('event_dispatcher')
        );
    }


    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var TokenManager
     */
    protected $tokenManager;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;


    public function __construct(
        EntityManager $entityManager,
        TokenManager $tokenManager,
        EventDispatcherInterface $dispatcher
    ) {
        $this->entityManager = $entityManager;
        $this->tokenManager = $tokenManager;
        $this->dispatcher = $dispatcher;
    }


    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'ucms_extranet_member_acceptance_form';
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
            $this->t("Accept the registration of !name?", ['!name' => $account->getDisplayName()]),
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

        // Enables the new member
        $account->status = 1;
        $this->entityManager->getStorage('user')->save($account);
        // Sends him the e-mail for the creation of his password
        $params = ['site' => $site];
        $this->tokenManager->sendTokenMail($account, 'ucms_extranet', 'new-member-accepted', $params);

        drupal_set_message($this->t("Registration of !name has been accepted.", ['!name' => $account->getDisplayName()]));

        $event = new ExtranetMemberEvent($account, $site);
        $this->dispatcher->dispatch(ExtranetMemberEvent::EVENT_ACCEPT, $event);
    }
}



