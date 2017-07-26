<?php

namespace MakinaCorpus\Ucms\Site\Controller\ArgumentResolver;

use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * Resolves current site from context
 */
class CurrentSiteValueResolver implements ArgumentValueResolverInterface
{
    private $siteManager;

    public function __construct(SiteManager $siteManager)
    {
        $this->siteManager = $siteManager;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Request $request, ArgumentMetadata $argument)
    {
        // In order to avoid confusion with controller listing sites data we
        // need to be precise on matching, hence the parameter name check.
        return $this->siteManager->hasContext() && Site::class === $argument->getType() && 'currentSite' === $argument->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(Request $request, ArgumentMetadata $argument)
    {
        yield $this->siteManager->getContext();
    }
}
