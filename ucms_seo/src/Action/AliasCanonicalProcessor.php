<?php

namespace MakinaCorpus\Ucms\Seo\Action;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\Action\AbstractActionProcessor;
use MakinaCorpus\Ucms\Seo\SeoService;

class AliasCanonicalProcessor extends AbstractActionProcessor
{
    use StringTranslationTrait;

    /**
     * @var SeoService
     */
    protected $service;

    /**
     * @var AccountInterface
     */
    protected $currentUser;

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

        parent::__construct($this->t("Set as canonical"), 'pushpin');
    }

    public function getId()
    {
        return 'canonicalize';
    }

    public function getQuestion($items, $totalCount)
    {
        return $this->formatPlural(
            $totalCount,
            "Set this item as canonical for the associated page?",
            "Set the selected @count items as canonical for their respectively associated pages?"
        );
    }

    public function appliesTo($item)
    {
        return $item instanceof \stdClass && property_exists($item, 'alias') && property_exists($item, 'source') && !$item->is_canonical;
    }

    public function processAll($items)
    {
        foreach ($items as $item) {
            $this->service->setCanonicalForAlias($item);
        }
    }

    public function getItemId($item)
    {
        return $item->pid;
    }

    public function loadItem($id)
    {
        // Convert the object to stdClass because the Drupal alias storage will
        // give us an array
        return (object)$this->service->getAliasStorage()->load(['pid' => $id]);
    }
}
