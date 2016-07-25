<?php


namespace MakinaCorpus\Ucms\Label;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Session\AccountInterface;


/**
 * Class to abstract functions of the taxonomy module.
 */
final class LabelManager
{

    /**
     * Constant which stores the vocabulary machine name.
     */
    const VOCABULARY_MACHINE_NAME = 'labels';


    /**
     * @var \DatabaseConnection
     */
    private $db;

    /**
     * @var EntityManager
     */
    private $entityManager;


    /**
     * Default constructor.
     *
     * @param \DatabaseConnection $db
     */
    public function __construct(\DatabaseConnection $db, EntityManager $entityManager)
    {
        $this->db = $db;
        $this->entityManager = $entityManager;
    }


    /**
     * Load the labels vocabulary.
     *
     * @return stdClass
     */
    public function loadVocabulary()
    {
        $entities = $this->entityManager
            ->getStorage('taxonomy_vocabulary')
            ->loadByProperties(['machine_name' => self::VOCABULARY_MACHINE_NAME]);

        return reset($entities);
    }


    /**
     * Get the ID of the labels vocabulary.
     *
     * @return integer
     */
    public function getVocabularyId()
    {
        return $this->loadVocabulary()->vid;
    }


    /**
     * Get the machine name of the labels vocabulary.
     *
     * @return string
     */
    public function getVocabularyMachineName()
    {
        return self::VOCABULARY_MACHINE_NAME;
    }


    /**
     * Load the labels matching the given identifiers.
     *
     * @param integer[] $ids
     * @return stdClass[]
     */
    public function loadLabels(array $ids)
    {
        return $this->entityManager->getStorage('taxonomy_term')->loadMultiple($ids);
    }


    /**
     * Load all labels
     *
     * @param integer[] $ids
     * @return stdClass[]
     */
    public function loadAllLabels()
    {
        return taxonomy_get_tree($this->getVocabularyId(), 0, null, true);
    }


    /**
     * Load the root labels.
     *
     * @return stdClass[]
     */
    public function loadRootLabels()
    {
        return taxonomy_get_tree($this->getVocabularyId(), 0, 1, true);
    }


    /**
     * Load the label's parent
     *
     * @param stdClass $label
     * @return stdClass
     */
    public function loadParent(\stdClass $label)
    {
        $parents = taxonomy_get_parents($label->tid);
        return reset($parents);
    }


    /**
     * Has the label some children?
     *
     * @param stdClass $label
     * @return boolean
     */
    public function hasChildren(\stdClass $label)
    {
        $q = $this->db
            ->select('taxonomy_term_hierarchy', 'h')
            ->condition('h.parent', $label->tid)
            ->fields('h', array('tid'))
            ->range(0, 1)
            ->execute();

        return !empty($q->fetch());
    }


    /**
     * Is the label a root term?
     *
     * @param stdClass $label
     * @return boolean
     */
    public function isRootLabel(\stdClass $label)
    {
        $q = $this->db
            ->select('taxonomy_term_hierarchy', 'h')
            ->condition('h.parent', 0)
            ->condition('h.tid', $label->tid)
            ->fields('h', array('tid'))
            ->range(0, 1)
            ->execute();

        return !empty($q->fetch());
    }


    /**
     * Save the label.
     *
     * @return integer Constant SAVED_NEW or SAVED_UPDATED.
     * @throws \LogicException
     * @see taxonomy_term_save().
     */
    public function saveLabel(\stdClass $label)
    {
        if (!empty($label->tid)) {
            // Prevents to save a label with a parent if it has children.
            // The labels vocabulary must have only two levels.
            if (!isset($label->original)) {
                $label->original = $this->entityManager->getStorage('taxonomy_term')->loadUnchanged($label->tid);
            }
            if (!isset($label->original->parent)) {
                $label->original->parent = 0;
            }
            if ($this->hasChildren($label) && $label->parent != $label->original->parent) {
                throw new \LogicException("Can't define a parent to a label which has children.");
            }
        }

        return taxonomy_term_save($label);
    }


    /**
     * Delete the label.
     *
     * @return integer Constant SAVED_DELETED if no exception occurs.
     * @throws \LogicException
     * @see taxonomy_term_delete().
     */
    public function deleteLabel(\stdClass $label)
    {
        // Prevents to delete a label which has children.
        if ($this->hasChildren($label)) {
            throw new \LogicException("Can't delete a label which has children.");
        }
        return taxonomy_term_delete($label->tid);
    }


    /**
     * Is the user allowed to edit the given label?
     *
     * @param stdClass $label
     * @param AccountInterface $account
     * @return boolean
     */
    public function canEditLabel(\stdClass $label, AccountInterface $account = null)
    {
        if ($account === null) {
            global $user;
            $account = $user;
        }

        return ($label->is_locked == 0)
            ? user_access(LabelAccess::PERM_EDIT_NON_LOCKED, $account)
            : user_access(LabelAccess::PERM_EDIT_LOCKED, $account);
    }


    /**
     * Is the user allowed to edit all labels (i.e. locked and non locked labels)?
     *
     * @param AccountInterface $account
     * @return boolean
     */
    public function canEditAllLabels(AccountInterface $account = null)
    {
        if ($account === null) {
            global $user;
            $account = $user;
        }

        return (
            user_access(LabelAccess::PERM_EDIT_NON_LOCKED, $account) &&
            user_access(LabelAccess::PERM_EDIT_LOCKED, $account)
        );
    }


    /**
     * Is the user allowed to edit locked labels?
     *
     * @param AccountInterface $account
     * @return boolean
     */
    public function canEditLockedLabels(AccountInterface $account = null)
    {
        if ($account === null) {
            global $user;
            $account = $user;
        }

        return user_access(LabelAccess::PERM_EDIT_LOCKED, $account);
    }


    /**
     * Is the user allowed to edit non locked labels?
     *
     * @param AccountInterface $account
     * @return boolean
     */
    public function canEditNonLockedLabels(AccountInterface $account = null)
    {
        if ($account === null) {
            global $user;
            $account = $user;
        }

        return user_access(LabelAccess::PERM_EDIT_NON_LOCKED, $account);
    }

}

