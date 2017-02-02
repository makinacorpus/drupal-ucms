<?php

namespace MakinaCorpus\Ucms\Seo\Action;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;

use MakinaCorpus\Drupal\Dashboard\Action\Action;
use MakinaCorpus\Drupal\Dashboard\Action\ActionProviderInterface;
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
            $ret[] = new Action($this->t("SEO parameters"), 'node/' . $item->id() . '/seo-edit', null, 'globe', 1, false, true, false, 'seo');
            $ret[] = new Action($this->t("SEO aliases"), 'node/' . $item->id() . '/seo-aliases', null, 'link', 2, false, false, false, 'seo');
            $ret[] = new Action($this->t("SEO redirects"), 'node/' . $item->id() . '/seo-redirects', null, 'random', 3, false, false, false, 'seo');
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
