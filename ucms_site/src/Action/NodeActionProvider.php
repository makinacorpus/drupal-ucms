<?php

namespace MakinaCorpus\Ucms\Site\Action;

use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\Action\ActionProviderInterface;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;

/**
 * The site module will add node actions, corresponding to reference
 * and cloning operations
 */
class NodeActionProvider implements ActionProviderInterface
{
    use StringTranslationTrait;

    /**
     * @var SiteManager
     */
    private $manager;

    /**
     * Default constructor
     *
     * @param SiteManager $manager
     */
    public function __construct(SiteManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * {inheritdoc}
     */
    public function getActions($item)
    {
        $ret = [];

        if ($this->manager->getAccess()->userCanReference($item, $GLOBALS['user']->uid /* fixme */)) {
            $ret[] = new Action($this->t("Reference it on my site"), 'node/' . $item->nid . '/reference', 'dialog', 'download-alt', 2, true, true);
        }

        return $ret;
    }

    /**
     * {inheritdoc}
     */
    public function supports($item)
    {
        // That should be enough
        return is_object($item) && property_exists($item, 'nid') && property_exists($item, 'is_global');
    }
}
