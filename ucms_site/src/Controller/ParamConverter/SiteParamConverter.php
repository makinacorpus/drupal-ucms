<?php

namespace MakinaCorpus\Ucms\Site\Controller\ParamConverter;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use MakinaCorpus\Ucms\Site\SiteManager;
use Symfony\Component\Routing\Route;

class SiteParamConverter implements ParamConverterInterface
{
    private $siteManager;

    /**
     * Default constructor
     */
    public function __construct(SiteManager $siteManager)
    {
        $this->siteManager = $siteManager;
    }

    /**
     * {@inheritdoc}
     */
    public function convert($value, $definition, $name, array $defaults)
    {
        try {
            return $this->siteManager->getStorage()->findOne($value);
        } catch (\InvalidArgumentException $e) {}

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function applies($definition, $name, Route $route)
    {
        return ($definition['type'] ?? null) === 'ucms_site';
    }
}
