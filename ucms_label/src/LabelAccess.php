<?php


namespace MakinaCorpus\Ucms\Label;


/**
 * Access constants
 */
final class LabelAccess
{

    /**
     * List operation
     */
    const OP_LIST = 'list';

    /**
     * Add operation
     */
    const OP_ADD = 'add';

    /**
     * Edit operation
     */
    const OP_EDIT = 'edit';

    /**
     * Delete operation
     */
    const OP_DELETE = 'delete';

    /**
     * Access site dashboard permission
     */
    const PERM_ACCESS_DASHBOARD = 'labels dashboard access';

    /**
     * Edit locked labels
     */
    const PERM_EDIT_LOCKED = 'labels edit locked';

    /**
     * Edit non locked labels only
     */
    const PERM_EDIT_NON_LOCKED = 'labels edit non locked';

}

