<?php

namespace MakinaCorpus\Ucms\Composition\Command;

use MakinaCorpus\Drupal\Sf\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Basic migration from ucms_layout command.
 */
class MigrateFromLayoutCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('ucms:composition:migate')
            ->setDescription('Migrate from the ucms_layout module')
            ->setHelp(<<<EOT
Migrates from the ucms_layout module to the ucms_composition one. This
procedure will wipe out all ucms_composition data, but will leave untouched the
ucms_layout data, allowing you to do many migration attempts without loosing
data.

If you need to merge some regions altogether but not all regions, use the
--squash option instead of the --merge-region, where its syntax is:

   --squash=(region1 region2[" "...])[" "...]

For example, if you wish to squash 'front1' and 'banner' regions, but also
'sidebar1' and 'column2' regions, you would write this:

    --squash="(front1 banner) (sidebar1 column2)"

Multiple important notes:

  - for each group, the first region occuring in the parenthesis will be the
    one being kept in the final region list;

  - whitespaces will be ignored.

EOT
            )
            ->addOption('force-recreate', null, InputOption::VALUE_NONE, "Drop all new layouts and recreate them")
            ->addOption('merge-regions', null, InputOption::VALUE_NONE, "Merge all regions altogether")
            ->addOption('region-as-style', null, InputOption::VALUE_NONE, "Use old region names as styles")
            ->addOption('squash', null, InputOption::VALUE_REQUIRED, "Region squash sets (see help for details)")
        ;
    }

    /**
     * Parse the squash option
     *
     * @param string $string
     *
     * @return null|string[][]
     */
    private function parseSquash(string $string) : array
    {
        // As always, regexes to the rescue
        $matches = [];
        // @todo find out why using /^...$/ does not work capturing all groups
        if (!preg_match_all('/((\(\w+(?:\s+\w+)+\)\s*)+?)/ui', trim($string), $matches)) {
            throw new \InvalidArgumentException();
        }

        $ret = [];

        foreach ($matches[1] as $group) {
            $ret[] = array_filter(preg_split('/[\(\)\s]+/', $group));
        }

        return $ret;
    }

    /**
     * It's the easiest procedure: it just merge the old schema into the new
     * one by doing a few temporary steps: very fast, very easy.
     */
    private function migrateWithSquash(array $groups, bool $useRegionAsStyle = false)
    {
        /** @var \DatabaseConnection $database */
        $database = $this->getContainer()->get('database');

        $database->query("
            delete from {layout}
        ");

        // Build the when case for the query
        $cases = [];
        $params = [];
        foreach ($groups as $index => $group) {
            $first = reset($group);
            foreach ($group as $pos => $region) {
                $argFirst = ':group_' . $index . '_first_' . $pos;
                $argCurrent = ':group_' . $index . '_current_' . $pos;
                $params[$argFirst] = $first;
                $params[$argCurrent] = $region;
                $cases[] = "when " . $argCurrent . " then " . $argFirst;
            }
        }

        // Example working command:
        //   bin/console -vvv ucms:composition:migate --region-as-style --squash="(content front1 front2 front3 front4) (footer1 footer2 footer3 footer4)"

        // This will happily fetch and merge all layout we want to migrate at once
        $database->query("
              create temporary table {temp_layout_migrate} as
              select
                  l.id as layout_id, l.nid, l.site_id,
                  case d.region
                      " . implode("\n                    ", $cases) . "
                      else d.region
                  end as new_region,
                  region,
                  0 as new_id
            from {ucms_layout} l
            join {ucms_layout_data} d
                on d.layout_id = l.id
            group by
                l.id, d.region, l.site_id, l.nid
        ", $params);

        // Because some regions we priorize on squashing might not contain
        // content, they will be inexistant in the temporary table, we need
        // to recreate them by looking up for missing layouts
        // DISTINCT here is important, it avoids duplicates
        $database->query("
            insert into {temp_layout_migrate} (
                layout_id, nid, site_id, region, new_region
            )
            select distinct
                ext.layout_id, ext.nid, ext.site_id, ext.new_region, ext.new_region
            from {temp_layout_migrate} ext
            where not exists (
                select 1
                from {temp_layout_migrate} int
                where
                    int.region = ext.new_region
                    and int.layout_id = ext.layout_id
            )
        ");

        // In opposition to programmatic simple merge algorithm that keeps all
        // regions, we must not create all layouts but only those whose are
        // tied to non-squashed regions, then the WHERE
        $database->query("
            insert into {layout} (site_id, node_id, region)
            select
                site_id, nid, region
            from {temp_layout_migrate}
            where
                region = new_region
        ", $params);

        // Fetch newly created layout identifiers for the very last request
        // to run
        $database->query("
            update temp_layout_migrate
            set
                new_id = (
                    select id
                    from layout l
                    where
                        l.region = temp_layout_migrate.new_region
                        and l.site_id = temp_layout_migrate.site_id
                        and l.node_id = temp_layout_migrate.nid
                        limit 1
                )
        ");

        // And here we go for the final data migration, layout items!
        if ($useRegionAsStyle) {
            $database->query("
                insert into {layout_data} (layout_id, item_type, item_id, style, position)
                select
                    t.new_id, 'node', d.nid, d.region, d.weight
                from {ucms_layout_data} d
                join {temp_layout_migrate} t
                    on t.layout_id = d.layout_id
                    and t.region = d.region
            ");
        } else {
            $database->query("
                insert into {layout_data} (layout_id, item_type, item_id, style, position)
                select
                    t.new_id, 'node', d.nid, d.view_mode, d.weight
                from {ucms_layout_data} d
                join {temp_layout_migrate} t
                    on t.layout_id = d.layout_id
                    and t.region = d.region
            ");
        }
    }

    /**
     * It's the easiest procedure: it just merge the old schema into the new
     * one by doing a few temporary steps: very fast, very easy.
     */
    private function migrateKeepingRegions(bool $useRegionAsStyle = false)
    {
        /** @var \DatabaseConnection $database */
        $database = $this->getContainer()->get('database');

        $database->query("
            delete from {layout}
        ");

        $database->query("
            create temporary table {temp_layout_migrate} as
            select
                l.id as layout_id, l.nid, l.site_id, region, 0 as new_id
            from {ucms_layout} l
            join {ucms_layout_data} d
                on d.layout_id = l.id
            group by
                l.id, d.region, l.site_id, l.nid
        ");

        $database->query("
            insert into {layout} (site_id, node_id, region)
            select
                site_id, nid, region
            from {temp_layout_migrate}
        ");

        $database->query("
            update {temp_layout_migrate}
            set
                new_id = (
                    select id
                    from {layout} l
                    where
                        l.region = {temp_layout_migrate}.region
                        and l.site_id = {temp_layout_migrate}.site_id
                        and l.node_id = {temp_layout_migrate}.nid
                )
        ");

        if ($useRegionAsStyle) {
            $database->query("
                insert into {layout_data} (layout_id, item_type, item_id, style, position)
                select
                    t.new_id, 'node', d.nid, d.region, d.weight
                from {ucms_layout_data} d
                join {temp_layout_migrate} t
                    on t.layout_id = d.layout_id
                    and t.region = d.region
            ");
        } else {
            $database->query("
                insert into {layout_data} (layout_id, item_type, item_id, style, position)
                select
                    t.new_id, 'node', d.nid, d.view_mode, d.weight
                from {ucms_layout_data} d
                join {temp_layout_migrate} t
                    on t.layout_id = d.layout_id
                    and t.region = d.region
            ");
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!db_table_exists('ucms_layout')) {
            $output->writeln("<error>Table 'ucms_layout' is not found, there is no data to migrate</error>");
            return -1;
        }

        if (!module_exists('phplayout') || !module_exists('ucms_composition')) {
            $output->writeln("one of the 'ucms_composition' or 'phplayout' module is disabled, enabling it");
            module_enable(['phplayout', 'ucms_composition']);
        }

        /** @var \DatabaseConnection $database */
        $database = $this->getContainer()->get('database');

        $exists = (bool)$database->query("select 1 from {layout}")->fetchField();
        if ($exists) {
            if (!$input->getOption('force-recreate')) {
                $output->writeln("<error>'layout' table contains data, use --force-recreate switch to drop all existing data before migrating</error>");
                return -1;
            }

            $output->writeln("<comment>'layout' table contains data and will be dropped</comment>");
        }

        $squash = null;
        $useRegionAsStyle = (bool)$input->getOption('region-as-style');

        if ($string = $input->getOption("squash")) {
            try {
                $squash = $this->parseSquash($string);
            } catch (\InvalidArgumentException $e) {
                $output->writeln('<error>given --squash parameter is invalid</error>');
                return -1;
            }
        }

        if ($squash) {
            $this->migrateWithSquash($squash, $useRegionAsStyle);
        } else if (!$input->getOption('merge-regions')) {
            $this->migrateKeepingRegions($useRegionAsStyle);
        }

        // Print a small synthesis
        $ucmsEmptyLayoutCount = $database->query("select count(*) from {ucms_layout} l where not exists (select 1 from {ucms_layout_data} where layout_id = l.id)")->fetchField();
        $ucmsLayoutCount      = $database->query("select count(*) from {ucms_layout}")->fetchField();
        $ucmsLayoutDataCount  = $database->query("select count(*) from {ucms_layout_data}")->fetchField();
        $layoutCount          = $database->query("select count(*) from {layout}")->fetchField();
        $layoutDataCount      = $database->query("select count(*) from {layout_data}")->fetchField();

        $output->writeln('<info>' . sprintf('%d layouts migrated into %d new ones', $ucmsLayoutCount, $layoutCount) . '</info>');
        $output->writeln('<info>' . sprintf('%d layouts were empty', $ucmsEmptyLayoutCount) . '</info>');
        $output->writeln('<info>' . sprintf('%d layouts items migrated into %d new ones', $ucmsLayoutDataCount, $layoutDataCount) . '</info>');
    }
}
