<?php

namespace MakinaCorpus\Ucms\Site\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class WebmasterDelete extends ConfirmFormBase
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

    protected $manager;
    protected $dispatcher;
    protected $site;
    protected $user;

    /**
     * Default constructor
     */
    public function __construct(SiteManager $manager, EventDispatcherInterface $dispatcher)
    {
        $this->manager = $manager;
        $this->dispatcher = $dispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, Site $site = null, AccountInterface $user = null)
    {
        $this->site = $site;
        $this->user = $user;

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'ucms_webmaster_delete';
    }

    /**
     * {@inheritdoc}
     */
    public function getQuestion()
    {
        return $this->t("Remove @name from @site webmasters?", [
            '@name' => $this->user->getDisplayName(),
            '@site' => $this->site->getAdminTitle(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getCancelUrl()
    {
        return new Url('ucms_site.admin.site.webmaster', ['site' => $this->site->getId()]);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $formState)
    {
        $access = $this->manager->getAccess()->getUserRole($this->user, $this->site);
        $this->manager->getAccess()->removeUsers($this->site, [$this->user->id()]);

        \drupal_set_message($this->t("@name has been removed from @site webmasters.", [
            '@name' => $this->user->getDisplayName(),
            '@site' => $this->site->getAdminTitle(),
        ]));

        $event = new SiteEvent($this->site, $this->currentUser()->id(), ['webmaster_id' => $this->user->id(), 'role' => $access->getRole()]);
        $this->dispatcher->dispatch('site:webmaster_delete', $event);

        $formState->setRedirect('ucms_site.admin.site.webmaster', ['site' => $this->site->getId()]);
    }
}



