<?php


namespace MakinaCorpus\Ucms\Site\Action;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\Action\ActionProviderInterface;
use MakinaCorpus\Ucms\Site\SiteAccessRecord;
use MakinaCorpus\Ucms\Site\SiteManager;

abstract class AbstractWebmasterActionProvider implements ActionProviderInterface
{
    use StringTranslationTrait;

    /**
     * @var SiteManager
     */
    protected $manager;

    /**
     * @var AccountInterface
     */
    protected $currentUser;

    /**
     * Default constructor
     *
     * @param SiteManager $manager
     */
    public function __construct(SiteManager $manager, AccountInterface $currentUser)
    {
        $this->manager = $manager;
        $this->currentUser = $currentUser;
    }

    /**
     * {@inheritdoc}
     *
     * @param SiteAccessRecord $item
     */
    abstract public function getActions($item);


    /**
     * {@inheritdoc}
     */
    public function supports($item)
    {
        return $item instanceof SiteAccessRecord;
    }

    /**
     * Builds the URI for a given operation on site accesses.
     *
     * @param SiteAccessRecord $item
     * @param string $op
     *
     * @return string
     */
    protected function buildWebmasterUri(SiteAccessRecord $item, $op)
    {
        return 'admin/dashboard/site/' . $item->getSiteId()
            . '/webmaster/' . $item->getUserId() . '/' . $op;
    }

    /**
     * Creates the action to delete a user from a site.
     *
     * @param SiteAccessRecord $item
     *
     * @return Action
     */
    protected function createDeleteAction(SiteAccessRecord $item)
    {
        $path = $this->buildWebmasterUri($item, 'delete');
        return new Action($this->t("Delete from this site"), $path, 'dialog', 'remove', 100, true, true);
    }

    /**
     * Creates the action to change the role of a user.
     *
     * @param SiteAccessRecord $item
     *
     * @return Action
     */
    protected function createChangeRoleAction(SiteAccessRecord $item)
    {
        $path = $this->buildWebmasterUri($item, 'change-role');
        return new Action($this->t("Change user's role"), $path, 'dialog', 'edit', 50, true, true);
    }
}

