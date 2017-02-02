<?php

namespace MakinaCorpus\Ucms\Seo\Action;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Drupal\Dashboard\Action\AbstractActionProcessor;
use MakinaCorpus\Ucms\Seo\SeoService;

class AliasDeleteProcessor extends AbstractActionProcessor
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

        parent::__construct($this->t("Delete"), 'trash', 500, true);
    }

    public function getId()
    {
        return 'alias_delete';
    }

    public function getQuestion($items, $totalCount)
    {
        return $this->formatPlural(
            $totalCount,
            "Delete this page alias?",
            "Delete those @count page aliases?"
        );
    }

    public function appliesTo($item)
    {
        // You may not delete the canonical alias
        return $item instanceof \stdClass && property_exists($item, 'alias') && property_exists($item, 'source') && !$item->is_canonical;
    }

    public function processAll($items)
    {
        foreach ($items as $item) {
            $this->service->getAliasStorage()->delete(['pid' => $item->pid]);
        }

        return $this->formatPlural(
            count($items),
            "Alias has been deleted",
            "@count aliases have been deleted"
        );
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
