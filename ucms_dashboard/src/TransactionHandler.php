<?php

namespace MakinaCorpus\Ucms\Dashboard;

/**
 * Service that allows you to do transaction without worrying about the database
 */
class TransactionHandler
{
    private $db;

    public function __construct(\DatabaseConnection $db)
    {
        $this->db = $db;
    }

    public function run(callable $success, callable $failure = null)
    {
        $tx = null;

        try {
            $tx = $this->db->startTransaction();

            $ret = call_user_func($success);

            unset($tx); // Explicit commit, because Drupal can't.

            return $ret;

        } catch (\Exception $e) {
            if ($tx) {
                try {
                    $tx->rollback();
                } catch (\Exception $e2) {
                    // You are seriously fucked
                    watchdog_exception('rollback', $e2);
                }
            }

            if ($failure) {
                return call_user_func($failure);
            }

            throw $e; // Not my problem
        }
    }
}
