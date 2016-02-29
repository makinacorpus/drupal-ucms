<?php

namespace MakinaCorpus\Ucms\Seo\Path;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Path\AliasStorageInterface;

/**
 * Implementation that will hit a custom table instead of use the {url_alias}
 * table, please note this will only work if all modules uses the alias manager
 * and alias storage instances instead of doing direct queries on the core
 * legacy table.
 */
class SeoAliasStorage implements AliasStorageInterface
{
    /**
     * @var \DatabaseConnection
     */
    protected $db;

    /**
     * @var ModuleHandlerInterface
     */
    protected $moduleHandler;

    /**
     * Default constructor
     *
     * @param \DatabaseConnection $db
     * @param ModuleHandlerInterface $moduleHandler
     */
    public function __construct(\DatabaseConnection $db, ModuleHandlerInterface $moduleHandler)
    {
        $this->db = $db;
        $this->moduleHandler = $moduleHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function save($source, $alias, $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED, $pid = null)
    {
        $path = [
            'source'    => $source,
            'alias'     => $alias,
            'language'  => $langcode,
        ];

        if ($pid) {
            $path['pid'] = $pid;

            $this
                ->db
                ->merge('ucms_seo_alias')
                ->key(['pid' => $pid])
                ->fields($path)
                ->execute()
            ;

            $this->moduleHandler->invokeAll('path_insert', [$path]);

        } else {

            $this
                ->db
                ->insert('ucms_seo_alias')
                ->fields($path)
                ->execute()
            ;

            $this->moduleHandler->invokeAll('path_update', [$path]);
        }

        \Drupal::service('path.alias_manager')->cacheClear($source);

        return $path;
    }

    /**
     * {@inheritdoc}
     */
    public function load($conditions)
    {
        $select = $this->db->select('ucms_seo_alias', 'u');

        foreach ($conditions as $field => $value) {
            if ($field == 'source' || $field == 'alias') {
                // Use LIKE for case-insensitive matching (stupid).
                $select->condition('u.' . $field, $this->db->escapeLike($value), 'LIKE');
            } else {
                if ('langcode' === $field) { // Drupal 7 compat
                    $field = 'language';
                }
                $select->condition('u.' . $field, $value);
            }
        }

        return $select
            ->fields('u')
            ->orderBy('u.pid', 'DESC')
            ->range(0, 1)
            ->execute()
            ->fetchAssoc('pid')
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($conditions)
    {
        $path = $this->load($conditions);
        $query = $this->db->delete('ucms_seo_alias');

        foreach ($conditions as $field => $value) {
            if ($field == 'source' || $field == 'alias') {
                // Use LIKE for case-insensitive matching (still stupid).
                $query->condition($field, $this->db->escapeLike($value), 'LIKE');
            } else {
                if ('langcode' === $field) { // Drupal 7 compat
                    $field = 'language';
                }
                $query->condition($field, $value);
            }
        }

        $deleted = $query->execute();
        // @todo Switch to using an event for this instead of a hook.
        $this->moduleHandler->invokeAll('path_delete', [$path]);

        return $deleted;
    }

    /**
     * {@inheritdoc}
     */
    public function lookupPathAlias($path, $langcode)
    {
        // See the queries above. Use LIKE for case-insensitive matching.
        $source = $this->db->escapeLike($path);

        $query = $this
            ->db
            ->select('ucms_seo_alias', 'u')
            ->fields('u', ['alias'])
            ->condition('u.source', $source, 'LIKE')
        ;

        if (LanguageInterface::LANGCODE_NOT_SPECIFIED === $langcode) {
            $langcodeList = [$langcode];
        } else {
            $langcodeList = [$langcode, LanguageInterface::LANGCODE_NOT_SPECIFIED];
            if (LanguageInterface::LANGCODE_NOT_SPECIFIED < $langcode) {
                $query->orderBy('u.language', 'DESC');
            } else {
                $query->orderBy('u.language', 'ASC');
            }
        }

        return $query
            ->orderBy('u.pid', 'DESC')
            ->condition('u.language', $langcodeList)
            ->execute()
            ->fetchField()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function lookupPathSource($path, $langcode)
    {
        // See the queries above. Use LIKE for case-insensitive matching.
        $alias = $this->db->escapeLike($path);

        $query = $this
            ->db
            ->select('ucms_seo_alias', 'u')
            ->fields('u', ['source'])
            ->condition('u.alias', $alias, 'LIKE')
        ;

        if (LanguageInterface::LANGCODE_NOT_SPECIFIED === $langcode) {
            $langcodeList = [$langcode];
        } else {
            $langcodeList = [$langcode, LanguageInterface::LANGCODE_NOT_SPECIFIED];
            if (LanguageInterface::LANGCODE_NOT_SPECIFIED < $langcode) {
                $query->orderBy('u.language', 'DESC');
            } else {
                $query->orderBy('u.language', 'ASC');
            }
        }

        return $query
            ->orderBy('u.pid', 'DESC')
            ->condition('u.language', $langcodeList)
            ->execute()
            ->fetchField()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function aliasExists($alias, $langcode, $source = null)
    {
        // Use LIKE and NOT LIKE for case-insensitive matching (stupid).
        $query = $this
            ->db
            ->select('ucms_seo_alias')
            ->condition('alias', $this->db->escapeLike($alias), 'LIKE')
            ->condition('language', $langcode)
        ;

        if (!empty($source)) {
            $query->condition('source', $this->db->escapeLike($source), 'NOT LIKE');
        }

        $query->addExpression('1');

        return (bool)$query
            ->range(0, 1)
            ->execute()
            ->fetchField()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getAliasesForAdminListing($header, $keys = NULL)
    {
        $query = $this
            ->db
            ->select('ucms_seo_alias', 'u')
            ->extend('PagerDefault')
            ->extend('TableSort')
        ;

        if ($keys) {
            // Replace wildcards with PDO wildcards.
            $values = '%' . preg_replace('!\*+!', '%', $keys) . '%';

            $query
                ->condition(
                    db_or()
                        ->condition('u.alias', $values, 'LIKE')
                        ->condition('u.source', $values, 'LIKE')
                )
            ;
        }

        return $query
            ->fields('u')
            ->orderByHeader($header)
            ->limit(50)
            ->execute()
            ->fetchAll()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function pathHasMatchingAlias($initial_substring)
    {
        $query = $this->db->select('ucms_seo_alias', 'u');
        $query->addExpression(1);

        return (bool)$query
            ->condition('u.source', $this->db->escapeLike($initial_substring) . '%', 'LIKE')
            ->range(0, 1)
            ->execute()
            ->fetchField()
        ;
    }
}
