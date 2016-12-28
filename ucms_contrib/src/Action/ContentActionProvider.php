<?php

namespace MakinaCorpus\Ucms\Contrib\Action;

use Drupal\Core\Session\AccountInterface;

use MakinaCorpus\Ucms\Contrib\ContentTypeManager;
use MakinaCorpus\Ucms\Dashboard\Action\AbstractActionProvider;
use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Site\NodeAccessService;
use MakinaCorpus\Ucms\Site\SiteManager;

/**
 * Class ContentActionProvider
 * @package MakinaCorpus\Ucms\Contrib\Action
 */
class ContentActionProvider extends AbstractActionProvider
{
    /**
     * @var ContentTypeManager
     */
    private $contentTypeManager;

    /**
     * @var SiteManager
     */
    private $siteManager;

    /**
     * @var AccountInterface
     */
    private $currentUser;

    /**
     * @var \MakinaCorpus\Ucms\Site\NodeAccessService
     */
    private $access;


    /**
     * ContentActionProvider constructor.
     *
     * @param ContentTypeManager $contentTypeManager
     * @param SiteManager $siteManager
     * @param AccountInterface $currentUser
     * @param NodeAccessService $access
     */
    public function __construct(
        ContentTypeManager $contentTypeManager,
        SiteManager $siteManager,
        AccountInterface $currentUser,
        NodeAccessService $access
    ) {
        $this->contentTypeManager = $contentTypeManager;
        $this->siteManager = $siteManager;
        $this->currentUser = $currentUser;
        $this->access = $access;
    }


    /**
     * {@inheritDoc}
     */
    public function getActions($item)
    {
        if ('cart' === $item) {
            return [
                Action::create([
                    'title'     => $this->t("Refresh"),
                    'uri'       => 'admin/cart/refresh/nojs',
                    'options'   => 'ajax',
                    'icon'      => 'refresh',
                    'primary'   => true,
                    'priority'  => -100,
                ])
            ];
        }

        // Add node creation link
        $actions = [];

        $siteAccess = $this->siteManager->getAccess();
        $names = $this->contentTypeManager->getTypeNames();

        $types = [
            'editorial' => $this->contentTypeManager->getEditorialTypes(),
            'component' => $this->contentTypeManager->getComponentTypes(),
            'media'     => $this->contentTypeManager->getMediaTypes(),
        ];

        foreach ($types[$item] as $index => $type) {
            $addCurrentDestination = ('media' === $item);

            $userIsContributor = (
                $siteAccess->userIsWebmaster($this->currentUser) ||
                $siteAccess->userIsContributor($this->currentUser)
            );

            if (
                !$this->siteManager->hasContext() &&
                $userIsContributor &&
                $this->access->userCanCreateInAnySite($this->currentUser, $type)
            ) {
                $label = $this->t('Create !content_type', ['!content_type' => $this->t($names[$type])]);

                // Edge case, we rewrite all options so that we don't add destination,
                // it will be handled by the form.
                $options = [
                    'attributes' => ['class' => ['use-ajax', 'minidialog']],
                    'query'      => ['minidialog'  => 1],
                ];
                $actions[] = new Action($label, 'node/add-to-site/' . strtr($type, '_', '-'), $options, null, $index, false, $addCurrentDestination, false, (string)$item);
            }
            else if (node_access('create', $type)) {
                $label = $this->t('Create !content_type', ['!content_type' => $this->t($names[$type])]);
                $actions[] = new Action($label, 'node/add/' . strtr($type, '_', '-'), null, null, $index, false, $addCurrentDestination, false, (string)$item);
            }
        }

        return $actions;
    }


    /**
     * {@inheritDoc}
     */
    public function supports($item)
    {
        return in_array($item, ['editorial', 'component', 'media']);
    }
}
