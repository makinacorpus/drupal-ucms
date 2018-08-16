<?php

namespace MakinaCorpus\Ucms\Site\Environment;

use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Drupal\Core\Entity\EntityTypeManager;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\Site\Security\AuthTokenStorage;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provide inter-site SSO.
 */
class CrossSiteAuthProvider implements AuthenticationProviderInterface
{
    const TOKEN_PARAMETER = 'ucms-auth';
    const TOKEN_SIZE = 32;

    private $authTokenStorage;
    private $entityTypeManager;
    private $siteManager;

    /**
     * Default constructor
     */
    public function __construct(AuthTokenStorage $authTokenStorage, EntityTypeManager $entityTypeManager, SiteManager $siteManager)
    {
        $this->authTokenStorage = $authTokenStorage;
        $this->entityTypeManager = $entityTypeManager;
        $this->siteManager = $siteManager;
    }

    /**
     * {@inheritdoc}
     */
    public function applies(Request $request)
    {
        if ($this->siteManager->hasContext()) {
            return self::TOKEN_SIZE === \strlen((string)$request->query->get(self::TOKEN_PARAMETER));
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(Request $request)
    {
        if (!$token = $request->query->get(self::TOKEN_PARAMETER)) {
            return null;
        }

        // We should not have to do this since the applies() method
        // has in theory be called prior to us, but better be safe
        // than sorry, context could have changed in so many ways.
        if (!$this->siteManager->hasContext()) {
            return null;
        }

        $siteId = $this->siteManager->getContext()->getId();
        $authToken = $this->authTokenStorage->find($siteId, $token);

        if ($authToken->isValid($token)) {
            return $this->entityTypeManager->getStorage('user')->load($authToken->getUserId());
        }

        // Opportunistic token deletion, this will help lowering
        // database table row volume. Notice it may delete a non
        // loaded instance which have a different token for the
        // same site.
        $this->authTokenStorage->delete($authToken->getSiteId(), $authToken->getUserId());
    }
}
