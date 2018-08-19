<?php

namespace MakinaCorpus\Ucms\Site\Environment;

use Drupal\Core\Entity\EntityTypeManager;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\Security\AuthTokenStorage;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class CrossSiteAuthProvider implements EventSubscriberInterface
{
    const TOKEN_PARAMETER = 'ucms-auth';

    private $authTokenStorage;
    private $entityTypeManager;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => [
                ['onRequest', 310] // Authenticator is 300
            ],
        ];
    }

    /**
     * Default constructor
     */
    public function __construct(AuthTokenStorage $authTokenStorage, EntityTypeManager $entityTypeManager)
    {
        $this->authTokenStorage = $authTokenStorage;
        $this->entityTypeManager = $entityTypeManager;
    }

    /**
     * After login redirect if necessary.
     *
     * The authenticate() method will be called by Drupal core on request
     * and this, by priority magic, will happen after it, allow us to set
     * a custom redirect response without the token parameter, which
     * ensures 2 different matters:
     *
     *  - redirect response will not be cached, there will be no token
     *    within the cache,
     *
     *  - url will be nicer for the user, auth token has nothing to do
     *    within, this also will prevent wrong 'destination' parameters
     *    from being generated.
     */
    public function onRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if (!$token = $request->query->get(self::TOKEN_PARAMETER)) {
            return;
        }

        // We should not have to do this since the applies() method
        // has in theory be called prior to us, but better be safe
        // than sorry, context could have changed in so many ways.
        if (!$site = Site::fromRequest($request)) {
            return;
        }

        $siteId = $site->getId();
        $authToken = $this->authTokenStorage->find($siteId, $token);

        if ($userId = $authToken->getUserId()) {
            // Valid or invalid, no matter, prevent the same token from being
            // used more than once for the same login session.
            $this->authTokenStorage->delete($authToken->getSiteId(), $userId);

            if ($authToken->isValid($token)) {
                if ($account = $this->entityTypeManager->getStorage('user')->load($userId)) {
                    \user_login_finalize($account);
                }
            }
        }

        // Doing this "a la main" instead of using Url::fromRequest() in
        // order to avoid exceptions because of a non existing path. Drupal
        // routing is a mess, it does way to much calculations.
        $url = $request->getRequestUri();
        $url = \preg_replace('/(&|)'.self::TOKEN_PARAMETER.'=[^&]*/', '', $url);
        $url = \trim($url, '?&');

        // This must be done even if login failed, else responses with wrong
        // tokens will be cached anyway.
        $response = new RedirectResponse($url);
        $response->setPrivate();
        $response->setVary(['Cookie']);

        $event->setResponse($response);
        $event->stopPropagation();
    }
}
