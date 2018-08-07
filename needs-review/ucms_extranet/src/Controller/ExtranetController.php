<?php


namespace MakinaCorpus\Ucms\Extranet\Controller;

use MakinaCorpus\Drupal\Sf\Controller;


class ExtranetController extends Controller
{
    /**
     * Action for the registration confirmation page.
     */
    public function confirmAction()
    {
        return $this->render('@ucms_extranet/views/confirmation.html.twig');
    }
}
