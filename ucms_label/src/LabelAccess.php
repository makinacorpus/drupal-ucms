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
     * View operation
     */
    const OP_VIEW_LOG = 'view_log';

    /**
     * Access site dashboard permission
     */
    const PERM_ACCESS_DASHBOARD = 'access labels dashboard';

    /**
     * Edit locked labels
     */
    const PERM_EDIT_LOCKED = 'edit locked labels';

    /**
     * Edit non locked labels only
     */
    const PERM_EDIT_NON_LOCKED = 'edit non locked labels';

}

