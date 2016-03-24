<?php

namespace MakinaCorpus\Ucms\Seo\Action;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;

use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\Action\ActionProviderInterface;
use MakinaCorpus\Ucms\Seo\SeoService;

class NodeActionProvider implements ActionProviderInterface
{
    use StringTranslationTrait;

    /**
     * @var SeoService
     */
    private $service;

    /**
     * @var AccountInterface
     */
    private $currentUser;

    /**
     * Default constructor
     *
     * @param SeoService $service
     * @param AccountInterface $currentUser
     */
    public function __construct(SeoService $service, AccountInterface $currentUser)
    {
        $this->service = $service;
        $this->currentUser = $currentUser;
    }

    /**
     * {inheritdoc}
     */
    public function getActions($item)
    {
        $ret = [];

        if ($this->service->userCanEditNodeSeo($this->currentUser, $item)) {
            $ret[] = new Action($this->t("SEO parameters"), 'node/' . $item->id() . '/seo-edit', null, 'globe', -2, false, true);
        }

        return $ret;
    }

    /**
     * {inheritdoc}
     */
    public function supports($item)
    {
        return $item instanceof NodeInterface;
    }
}
