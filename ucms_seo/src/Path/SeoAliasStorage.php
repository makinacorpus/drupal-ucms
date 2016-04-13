<?php

namespace MakinaCorpus\Ucms\Seo\Path;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Path\AliasStorageInterface;

use MakinaCorpus\Ucms\Site\SiteManager;

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
     * @var SiteManager
     */
    protected $siteManager;

    /**
     * Default constructor
     *
     * @param \DatabaseConnection $db
     * @param ModuleHandlerInterface $moduleHandler
     * @param SiteManager $siteManager
     */
    public function __construct(\DatabaseConnection $db, ModuleHandlerInterface $moduleHandler, SiteManager $siteManager)
    {
        $this->db = $db;
        $this->moduleHandler = $moduleHandler;
        $this->siteManager = $siteManager;
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
            'site_id'   => null,
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

    protected function ensureSiteContext(\SelectQuery $query)
    {
        // BEWARE: HERE BE DRAGONS (probably, anyway).
        // When looking up aliases, we need to filter using the current site
        // this would have no use otherwise to set the site_id on the node
        // information
        if ($this->siteManager->hasContext()) {
            $query->condition(
                db_or()
                    ->condition('u.site_id', $this->siteManager->getContext()->getId())
                    ->isNull('u.site_id')
            );
            // https://stackoverflow.com/questions/9307613/mysql-order-by-null-first-and-desc-after
            $query->orderBy('u.site_id IS NULL', 'desc');
        } else {
            $query->isNull('u.site_id');
        }
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

        // Canonical property will never be set automatically sus ensuring that
        // what the user tells is what the user gets, so caninonical is *always*
        // the alias we need to fetch to deambiguate
        $query->orderBy('u.is_canonical', 'DESC');

        $this->ensureSiteContext($query);

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

        // Source has no use of being restricted by the site identifier.

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

        $this->ensureSiteContext($query);

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
    public function preloadPathAlias($sources, $langcode)
    {
        // VERY IMPORTANT PIECE OF DOCUMENTATION, BECAUSE CORE DOES NOT
        // DOCUMENT IT VERY WELL:
        //  - the query inverse all the orders 'pid' and 'language' compared
        //    to the original ::lookupPathAlias() method
        //  - smart little bitches, it seems they didn't know how to write it
        //    correctly in SQL (and neither do I actually) - so they rely on
        //    the fetchAllKeyed() method, which iterates in order on the rows
        //    making them squashing the previously fetched one

        $query = $this
            ->db
            ->select('ucms_seo_alias', 'u')
            ->fields('u', ['source', 'alias'])
        ;

        $condition = new \DatabaseCondition('OR');
        foreach ($sources as $source) {
            // See the queries above. Use LIKE for case-insensitive matching.
            $condition->condition('u.source', $this->db->escapeLike($source), 'LIKE');
        }
        $query->condition($condition);

        if (LanguageInterface::LANGCODE_NOT_SPECIFIED === $langcode) {
            $langcodeList = [$langcode];
        } else {
            $langcodeList = [$langcode, LanguageInterface::LANGCODE_NOT_SPECIFIED];
            // !!! condition here is inversed from the lookupPathAlias() method
            if (LanguageInterface::LANGCODE_NOT_SPECIFIED > $langcode) {
                $query->orderBy('u.language', 'DESC');
            } else {
                $query->orderBy('u.language', 'ASC');
            }
        }

        // Canonical property will never be set automatically sus ensuring that
        // what the user tells is what the user gets, so caninonical is *always*
        // the alias we need to fetch to deambiguate
        // !!! condition here is inversed from the lookupPathAlias() method
        $query->orderBy('u.is_canonical', 'ASC');

        $this->ensureSiteContext($query);

        return $query
            // !!! condition here is inversed from the lookupPathAlias() method
            ->orderBy('u.pid', 'ASC')
            ->condition('u.language', $langcodeList)
            ->execute()
            ->fetchAllKeyed()
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
                    (new \DatabaseCondition('OR'))
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

    /**
     * {@inheritdoc}
     */
    public function getWhitelist()
    {
        return $this->db->query("SELECT DISTINCT SUBSTRING_INDEX(source, '/', 1) AS path FROM {ucms_seo_alias}")->fetchCol();
    }
}
