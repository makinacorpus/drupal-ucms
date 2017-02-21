<?php

namespace MakinaCorpus\Ucms\Seo\Action;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Drupal\Dashboard\Action\AbstractActionProcessor;
use MakinaCorpus\Ucms\Seo\Path\Redirect;
use MakinaCorpus\Ucms\Seo\SeoService;

/**
 * Delete action on redirect entries.
 */
class RedirectDeleteProcessor extends AbstractActionProcessor
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
        return 'redirect_delete';
    }

    public function getQuestion($items, $totalCount)
    {
        return $this->formatPlural(
            $totalCount,
            "Delete this node redirect?",
            "Delete those @count node redirects?"
        );
    }

    public function appliesTo($item)
    {
        return $item instanceof Redirect;
    }

    public function processAll($items)
    {
        foreach ($items as $item) {
            $this->service->getRedirectStorage()->delete(['id' => $item->getId()]);
        }

        return $this->formatPlural(
            count($items),
            "Redirect has been deleted",
            "@count redirects have been deleted"
        );
    }

    public function getItemId($item)
    {
        return $item->getId();
    }

    public function loadItem($id)
    {
        return $this->service->getRedirectStorage()->load(['id' => $id]);
    }
}
