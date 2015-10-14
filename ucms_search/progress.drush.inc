<?php
/**
 * Code took from
 *   https://www.drupal.org/node/1744410
 * Then fixed.
 */

/**
 * Create and return a new progress bar.
 *
 * Usage as follow:
 * @code
 *   $total = 1000;
 *   $bar = drush_create_progress();
 *   for ($i = 0; $i < $total; ++$i) {
 *     do_something_with($i);
 *     $bar->update($i / $total);
 *     // Or alternatively you could use:
 *     $bar->setProgress($total, $i);
 *   }
 *   $bar->end();
 * @endcode
 *
 * @return \DrushProgressBar
 *   Progress bar instance.
 */
function drush_create_progress() {
  return new DrushProgressBar();
}

/**
 * Single progress bar instance.
 */
class DrushProgressBar
{
    /**
     * @var boolean
     */
    private $doDisplay = true;

    public function __construct($onlyVerbose = false)
    {
        $verbose = drush_get_context('DRUSH_VERBOSE') || drush_get_context('DRUSH_DEBUG');

        // If the function is being called in quiet mode, or we're in
        // verbose/debug mode, simply return without printing anything.
        if (drush_get_context('DRUSH_QUIET') || (!$verbose && $onlyVerbose) || drush_get_context('DRUSH_BACKEND')) {
            $this->doDisplay = false;
        }
    }

    /**
     * Update the progress bar
     *
     * @param float $ratio
     */
    public function update($ratio)
    {
        if ($this->doDisplay) {
            $this->display($ratio);
        } else {
            $this->log($ratio);
        }
    }

    /**
     * Update with value
     *
     * @param int $total
     * @param int $limit
     */
    public function setProgress($total, $limit)
    {
        $this->update($limit / $total);
    }

    /**
     * Terminate the progess bar
     */
    public function end()
    {
        $this->update(1);

        if ($this->doDisplay) {
            print "\n";
        }
    }

    /**
     * Convert given ratio as percentage rounded value
     *
     * @param float $ratio
     */
    protected function getValue($ratio)
    {
        return min([
            max([
                0,
                round($ratio * 100),
            ]),
            100,
        ]);
    }

    /**
     * Display current ration in progress bar
     *
     * @param float $ratio
     */
    protected function display($ratio)
    {
        $percentage = $this->getValue($ratio) . '%';
        $columns = drush_get_context('DRUSH_COLUMNS', 80);
        // Subtract 8 characters for the percentage, brackets, spaces and arrow.
        $progress_columns = $columns - 8;
        // If ratio is 1 (complete), the > becomes a = to make a full bar.
        $arrow = ($ratio < 1) ? '>' : '=';
        // Print a new line if ratio is 1 (complete). Otherwise, use a CR.
        $line_ending = "\r";

        // Determine the current length of the progress string.
        $current_length = floor($ratio * $progress_columns);
        $progress_string = str_pad('', $current_length, '=');

        $output  = '[';
        $output .= $progress_string . $arrow;
        $output .= str_pad('', $progress_columns - $current_length);
        $output .= ']';
        $output .= str_pad('', 5 - strlen($percentage)) . $percentage;
        $output .= $line_ending;

        print $output;
    }

    /**
     * Log current ratio
     *
     * @param float $ratio
     */
    protected function log($ratio)
    {
        drush_log(dt('@percent completed.', ['@percent' => $this->getValue($ratio) . '%']), 'status');
    }
}
