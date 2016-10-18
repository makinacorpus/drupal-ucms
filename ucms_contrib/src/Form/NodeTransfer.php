<?php


namespace MakinaCorpus\Ucms\Contrib\Form;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;

use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\SiteAccessRecord;
use MakinaCorpus\Ucms\Site\SiteManager;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;


/**
 * Form to transfer the ownership of a node.
 */
class NodeTransfer extends FormBase
{
    /**
     * {@inheritdoc}
     */
    static public function create(ContainerInterface $container)
    {
        return new self(
            $container->get('entity.manager'),
            $container->get('ucms_site.manager'),
            $container->get('event_dispatcher')
        );
    }


    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var SiteManager
     */
    protected $siteManager;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;


    public function __construct(
        EntityManager $entityManager,
        SiteManager $siteManager,
        EventDispatcherInterface $dispatcher
    ) {
        $this->entityManager = $entityManager;
        $this->siteManager = $siteManager;
        $this->dispatcher = $dispatcher;
    }


    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'ucms_contrib_transfer_ownership';
    }


    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $formState, NodeInterface $node = null)
    {
        $formState->setTemporaryValue('node', $node);

        $form['#form_horizontal'] = false;

        $form['user'] = [
            '#type' => 'textfield',
            '#title' => $this->t("Transfer ownership to:"),
            '#description' => $this->t("Please enter a name and select the user in the suggestions list."),
            '#autocomplete_path' => 'admin/dashboard/site/users-ac',
            '#required' => true,
        ];

        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t("Transfer"),
        ];

        return $form;
    }


    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $formState)
    {
        if (preg_match('/\[(\d+)\]$/', $formState->getValue('user'), $matches) !== 1 || $matches[1] < 2) {
            $formState->setErrorByName('user', $this->t("The user could not be identified."));
        } else {
            $account = $this->entityManager->getStorage('user')->load($matches[1]);
            if (null === $account) {
                $formState->setErrorByName('user', $this->t("The user doesn't exist."));
            } else {
                $node = $formState->getTemporaryValue('node');
                // The node is a local content: no problem, the new owner will
                // be added to the sites's contributors if needed.
                // The node is a global: the new owner must
                // have the good permissions (then we check the access to the
                // update operation).
                if (empty($node->site_id) && !$node->access(Access::OP_UPDATE, $account)) {
                    $formState->setErrorByName('user', $this->t("The user %user is not allowed to manage this type of content.", ['%user' => $account->getDisplayName()]));
                } else {
                    $formState->setTemporaryValue('account', $account);
                }
            }
        }
    }


    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $formState)
    {
        /** @var $node Drupal\node\NodeInterface */
        $node = $formState->getTemporaryValue('node');
        /** @var $account Drupal\user\UserInterface */
        $account = $formState->getTemporaryValue('account');

        $node->setOwner($account);

        // User will be redirected in the admin, in most cases, nodes must be
        // reindexed right away to ensure that the admin will reflect the
        // change.
        $node->ucms_index_now = true;

        $this->entityManager->getStorage('node')->save($node);

        if ($node->site_id != null) {
            /** @var Site $site */
            $site = $this->siteManager->getStorage()->findOne($node->site_id);
            $role = $this->siteManager->getAccess()->getUserRole($account, $site);

            if (
                !($role instanceof SiteAccessRecord) ||
                !in_array($role->getRole(), [Access::ROLE_WEBMASTER, Access::ROLE_CONTRIB])
            ) {
                $this->siteManager->getAccess()->addContributors($site, $account->id());
            }
        }

        drupal_set_message($this->t("The content %title has been transferred to %user.", [
            '%title' => $node->getTitle(),
            '%user' => $account->getDisplayName(),
        ]));
    }
}

