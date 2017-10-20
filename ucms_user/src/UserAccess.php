<?php

namespace MakinaCorpus\Ucms\User;

/**
 * Access constants
 */
final class UserAccess
{
    /**
     * Manage all users: access dashboard, create, edit, delete, etc...
     */
    const PERM_MANAGE_ALL = 'users manage all';

    /**
     * Do not matter about context, you are the king.
     */
    const PERM_USER_GOD = 'users god';
}
