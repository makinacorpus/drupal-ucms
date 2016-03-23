<?php


namespace MakinaCorpus\Ucms\Contrib\Action;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Contrib\TypeHandler;
use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\Action\ActionProviderInterface;
use MakinaCorpus\Ucms\Site\SiteManager;


/**
 * Class ContentActionProvider
 * @package MakinaCorpus\Ucms\Contrib\Action
 */
class ContentActionProvider implements ActionProviderInterface
{
    use StringTranslationTrait;


    /**
     * @var TypeHandler
     */
    private $typeHandler;

    /**
     * @var SiteManager
     */
    private $siteManager;

    /**
     * @var AccountInterface
     */
    private $currentUser;


    /**
     * ContentActionProvider constructor.
     *
     * @param TypeHandler $typeHandler
     * @param SiteManager $siteManager
     */
    public function __construct(TypeHandler $typeHandler, SiteManager $siteManager, AccountInterface $currentUser)
    {
        $this->typeHandler = $typeHandler;
        $this->siteManager = $siteManager;
        $this->currentUser = $currentUser;
    }


    /**
     * {@inheritDoc}
     */
    public function getActions($item)
    {
        // Add node creation link
        $actions = [];

        $siteAccess = $this->siteManager->getAccess();
        $names = node_type_get_names();

        $types = [
            'editorial' => $this->typeHandler->getEditorialContentTypes(),
            'component' => $this->typeHandler->getComponentTypes(),
            'media'     => $this->typeHandler->getMediaTypes(),
        ];

        foreach ($types[$item] as $index => $type) {
            if (
                !$this->siteManager->hasContext() &&
                ($siteAccess->userIsWebmaster($this->currentUser) || $siteAccess->userIsContributor($this->currentUser))
            ) {
                $label = $this->t('Create !content_type', ['!content_type' => $this->t($names[$type])]);
                // Edge case, we rewrite all options so that we don't add destination, it will be handled by the form.
                $options = [
                    'attributes' => ['class' => ['use-ajax', 'minidialog']],
                    'query'      => ['minidialog'  => 1],
                ];
                $actions[] = new Action($label, 'node/add-to-site/' . strtr($type, '_', '-'), $options, null, $index, false);
            }
            elseif (node_access('create', $type)) {
                $label = $this->t('Create !content_type', ['!content_type' => $this->t($names[$type])]);
                $actions[] = new Action($label, 'node/add/' . strtr($type, '_', '-'), null, null, $index, false);
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
