<?php


namespace MakinaCorpus\Ucms\Site\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvent;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;


/**
 * Form to demote a webmaster as contributor.
 */
class WebmasterDemote extends FormBase
{
    /**
     * {inheritdoc}
     */
    static public function create(ContainerInterface $container)
    {
        return new self(
            $container->get('ucms_site.manager'),
            $container->get('event_dispatcher')
        );
    }


    /**
     * @var SiteManager
     */
    protected $manager;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;


    public function __construct(SiteManager $manager, EventDispatcherInterface $dispatcher)
    {
        $this->manager = $manager;
        $this->dispatcher = $dispatcher;
    }


    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'ucms_webmaster_demote';
    }


    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, Site $site = null, AccountInterface $user = null)
    {
        if (null === $site || null === $user) {
            return [];
        }

        $form_state->setTemporaryValue('site', $site);
        $form_state->setTemporaryValue('user', $user);

        return confirm_form(
            $form,
            $this->t("Demote @name as contributor?", ['@name' => $user->name]),
            'admin/dashboard/site/' . $site->id . '/webmaster',
            ''
        );
    }


    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $site = $form_state->getTemporaryValue('site');
        $user = $form_state->getTemporaryValue('user');
        $this->manager->getAccess()->addContributors($site, $user->id());

        drupal_set_message($this->t("!name has been demoted as %role.", [
            '!name' => $user->getDisplayName(),
            '%role' => $this->manager->getAccess()->getRelativeRoleName(Access::ROLE_CONTRIB),
        ]));
        $event = new SiteEvent($site, $this->currentUser()->id(), ['uid' => $user->id()]);
        $this->dispatcher->dispatch('site:demote_webmaster', $event);
    }
}



