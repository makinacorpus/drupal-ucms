<?php

namespace MakinaCorpus\Ucms\Dashboard\Controller;

use MakinaCorpus\Drupal\Sf\Controller;

class DbLogController extends Controller
{
    /**
     * @return \DatabaseConnection
     */
    private function getDatabase()
    {
        return $this->get('database');
    }

    public function displayLogAction()
    {
        $isDebug = $this->getParameter('kernel.debug');

        if (!$isDebug) {
            throw $this->createAccessDeniedException();
        }
        if (!module_exists('dblog')) {
            throw $this->createNotFoundException();
        }

        $entries = $this
            ->getDatabase()
            ->select('watchdog', 'w')
            ->fields('w')
            ->orderBy('w.timestamp', 'desc')
            ->range(0, 20)
            ->execute()
            ->fetchAll()
        ;

        if ($entries) {
            foreach ($entries as $entry) {

                $entry->variables = unserialize($entry->variables);
                if ($entry->variables) {
                    $text = t($entry->message, $entry->variables);
                } else {
                    $text = $entry->message;
                }
                $entry->message = text_summary($text, null, 150);

                switch ($entry->severity) {

                    case WATCHDOG_DEBUG:
                    case WATCHDOG_INFO:
                    case WATCHDOG_NOTICE:
                        $entry->severity = 'info';
                        break;

                    case WATCHDOG_WARNING:
                    case WATCHDOG_ALERT:
                        $entry->severity = 'warning';
                        break;

                    case WATCHDOG_ERROR:
                    case WATCHDOG_CRITICAL:
                        $entry->severity = 'danger';
                        break;
                }
            }
        }

        return $this->renderView('module:ucms_dashboard:views/DbLog/displayLog.html.twig', ['entries' => $entries]);
    }
}
