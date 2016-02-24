<?php


namespace MakinaCorpus\Ucms\Site\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvent;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;


/**
 * Form to assign a webmaster/contributor to a site.
 */
class WebmasterAddExisting extends FormBase
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
        return 'ucms_webmaster_add_existing';
    }


    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, Site $site = null)
    {
        if (null === $site) {
            return [];
        }

        $form['#form_horizontal'] = true;

        $form['name'] = [
            '#type' => 'textfield',
            '#title' => $this->t("Name"),
            '#description' => $this->t("Thanks to make your choice in the suggestions list."),
            '#autocomplete_path' => 'admin/dashboard/site/users-ac',
            '#required' => true,
        ];

        $form['role'] = [
            '#type' => 'radios',
            '#title' => $this->t("Role"),
            '#options' => [
                Access::ROLE_WEBMASTER  => $this->t("Webmaster"),
                Access::ROLE_CONTRIB    => $this->t("Contributor"),
            ],
            '#required' => true,
        ];

        $form['site'] = [
            '#type' => 'value',
            '#value' => $site->id,
        ];

        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t("Add"),
        ];

        return $form;
    }


    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        $user = $form_state->getValue('name');

        if (preg_match('/\[(\d+)\]$/', $user, $matches) !== 1 || $matches[1] < 2) {
            $form_state->setErrorByName('name', $this->t("User not reconized."));
        } else {
            $form_state->setTemporaryValue('user_id', $matches[1]);
        }
    }


    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        /* @var Site $site */
        $site = $this->manager->getStorage()->findOne($form_state->getValue('site'));

        if ((int) $form_state->getValue('role') === Access::ROLE_WEBMASTER) {
            $this->manager->getAccess()->addWebmasters($site, $form_state->getTemporaryValue('user_id'));
        } else {
            $this->manager->getAccess()->addContributors($site, $form_state->getTemporaryValue('user_id'));
        }
    }
}

