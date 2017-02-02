<?php

namespace MakinaCorpus\Ucms\Seo\Action;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;

use MakinaCorpus\Drupal\Dashboard\Action\AbstractActionProcessor;
use MakinaCorpus\Ucms\Seo\SeoService;
use MakinaCorpus\Ucms\Seo\StoreLocator\StoreLocatorFactory;

/**
 * Rebuilds all aliases of children element for a store locator.
 *
 * This seems legit to propose such actions, we cannot allow ourselves to
 * automatically rebuild them on every store locator node save, it would be
 * too much a performance drain on the platform, instead we do give the user
 * the chance to do this manually if some aliases are missing.
 */
class StoreLocatorAliasRebuildProcessor extends AbstractActionProcessor
{
    use StringTranslationTrait;

    private $currentUser;
    private $entityManager;
    private $locatorFactory;

    /**
     * Default constructor
     *
     * @param SeoService $service
     * @param AccountInterface $currentUser
     */
    public function __construct(StoreLocatorFactory $factory, EntityManager $entityManager, AccountInterface $currentUser)
    {
        $this->locatorFactory = $factory;
        $this->entityManager = $entityManager;
        $this->currentUser = $currentUser;

        parent::__construct($this->t("Rebuild SEO aliases"), 'cog', 1000, false, true, true, 'seo');
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'store_locator_aliases_rebuild';
    }

    /**
     * {@inheritdoc}
     */
    public function getQuestion($items, $totalCount)
    {
        return $this->formatPlural(
            $totalCount,
            "Rebuild all aliases for content linked to this store locator?",
            "Rebuild all aliases for content linked to these @count store locators?"
        );
    }

    /**
     * {@inheritdoc}
     */
    public function appliesTo($item)
    {
        $types = variable_get('ucms_seo_store_locator_content_types', []);

        /** @var \Drupal\node\NodeInterface $item */
        return $item instanceof NodeInterface && in_array($item->bundle(), $types);
    }

    /**
     * {@inheritdoc}
     */
    public function processAll($items)
    {
        foreach ($items as $item) {
            $this->locatorFactory->create($item)->rebuildAliases();
        }

        return $this->t("All aliases have been rebuilt");
    }

    /**
     * {@inheritdoc}
     */
    public function getItemId($item)
    {
        /** @var \Drupal\node\NodeInterface $item */
        return $item->id();
    }

    /**
     * {@inheritdoc}
     */
    public function loadItem($id)
    {
        return $this->entityManager->getStorage('node')->load($id);
    }
}
