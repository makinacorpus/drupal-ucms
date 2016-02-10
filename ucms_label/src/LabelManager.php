<?php


namespace MakinaCorpus\Ucms\Label;

use MakinaCorpus\Ucms\Label\Error\LabelDeletionException;


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
    protected $db;


    /**
     * Default constructor.
     *
     * @param \DatabaseConnection $db
     */
    public function __construct(\DatabaseConnection $db)
    {
        $this->db = $db;
    }


    /**
     * Load the labels vocabulary.
     *
     * @return stdClass
     */
    public function loadVocabulary()
    {
        return taxonomy_vocabulary_machine_name_load(self::VOCABULARY_MACHINE_NAME);
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
        return taxonomy_term_load_multiple($ids);
    }


    /**
     * Load the root labels.
     *
     * @return stdClass[]
     */
    public function loadRootLabels()
    {
        return taxonomy_get_tree($this->getVocabularyId(), 0, 1, TRUE);
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
     * Save the label.
     *
     * @return integer Constant SAVED_NEW or SAVED_UPDATED.
     * @see taxonomy_term_save().
     */
    public function saveLabel(\stdClass $label)
    {
        return taxonomy_term_save($label);
    }


    /**
     * Delete the label.
     *
     * @return integer Constant SAVED_DELETED if no exception occurs.
     * @see taxonomy_term_delete().
     */
    public function deleteLabel(\stdClass $label)
    {
        if ($this->hasChildren($label)) {
            throw new LabelDeletionException("Can't delete a label which has children.");
        }
        return taxonomy_term_delete($label->tid);
    }


    /**
     * Is the user allowed to edit the given label?
     *
     * @param stdClass $label
     * @param stdClass $account
     * @return boolean
     */
    public function canEditLabel(\stdClass $label, \stdClass $account = null)
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
     * @param stdClass $account
     * @return boolean
     */
    public function canEditAllLabels(\stdClass $account = null)
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
     * @param stdClass $account
     * @return boolean
     */
    public function canEditLockedLabels(\stdClass $account = null)
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
     * @param stdClass $account
     * @return boolean
     */
    public function canEditNonLockedLabels(\stdClass $account = null)
    {
        if ($account === null) {
            global $user;
            $account = $user;
        }

        return user_access(LabelAccess::PERM_EDIT_NON_LOCKED, $account);
    }

}

