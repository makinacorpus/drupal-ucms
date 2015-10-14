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
 * @param boolean $withEta
 *   Display ETA.
 * @param boolean $onlyVerbose
 *   Do not display the progress bar when not in verbose mode.
 *
 * @return \DrushProgressBar
 *   Progress bar instance.
 */
function drush_create_progress($withEta = true, $onlyVerbose = false) {
  return new DrushProgressBar($withEta, $onlyVerbose);
}

/**
 * Single progress bar instance.
 */
class DrushProgressBar
{
    /**
     * Format with one (of year, month, day, hour, min, s) element granularity.
     */
    const FORMAT_TIME_MORE_THAN = 1;

    /**
     * Format full human readable string.
     */
    const FORMAT_TIME_FULL = 2;

    /**
     * Format as fixed size string (HH:MM:SS) if possible.
     */
    const FORMAT_TIME_FIXED = 3;

    /**
     * Compute ETA
     *
     * @param int $start
     *   Start timestamp.
     * @param float $progress
     *   Number between 0 and 1.
     * @param int $now
     *   If providen, relative timestamp that substitutes now.
     *
     * @return int[]
     *   null in case it was not possible to compute ETA, otherwise:
     *     - first int is the current duration in seconds
     *     - second int the estimated ETA in seconds
     */
    public static function computeEta($start, $progress, $now = null)
    {
        if (!$now) {
            $now = time();
        }

        $elasped = $now - $start;

        if (!$elasped) {
            return; // Too soon, cannot compute anything
        }

        return [
            $elasped,
            self::estimateEta($elasped, $progress)
        ];
    }

    public static function estimateEta($duration, $progress)
    {
        if ($progress > 0) {
            // Another way of writing it:
            // return ((1 - $progress) * $duration) / $progress;
            // Fun note: doing some benchmarks, with 20 millions iterations
            // and incredebly stupid float values, both are more or less
            // exactly the same.
            // First one is from me, second one from Blaz.
            return $duration / $progress - $duration;
        }
    }

  /**
   * Format duration.
   *
   * @param int $duration
   *   Time count in seconds.
   *
   * @return string
   */
  public static function formatDuration($duration, $format = self::FORMAT_TIME_FIXED)
  {
      if (!$duration) {
          return 0;
      }

      $output = '';

      $year  = round( $duration / 31536000);
      $month = round(($duration /  2592000) % 31536000);
      $day   = round(($duration /    86400) %  2592000);
      $hour  = round(($duration /     3600) %    86400);
      $min   = round(($duration /       60) %     3600);
      $sec   = round( $duration             %       60);

      switch ($format) {

          case self::FORMAT_TIME_FIXED:
              if ($duration < 86400) {
                  $output .= sprintf("%02d", $hour) . ':';
                  $output .= sprintf("%02d", $min ) . ':';
                  $output .= sprintf("%02d", $sec );
                  break;
              }
              // If more than one day, leave the "more than" formatting.

          case self::FORMAT_TIME_MORE_THAN:
              if ($year) {
                  $output .= '> ' . format_plural($year,  "@count year",  "@count years");
              } else if ($month) {
                  $output .= '> ' . format_plural($month, "@count month", "@count monthes");
              } else if ($day) {
                  $output .= '> ' . format_plural($day,   "@count day",   "@count days");
              } else if ($hour) {
                  $output .= '~ ' . format_plural($hour,  "@count h",     "@count h");
              } else if ($min) {
                  $output .= '~ ' . format_plural($min,   "@count min",   "@count min");
              } else if ($sec) {
                  $output .= '~ ' . format_plural($sec,   "@count s",     "@count s");
              }
              break;

          case self::FORMAT_TIME_FULL:
              if ($year) {
                  $output .= format_plural($year,  "@count year",  "@count years")   . ' ';
              }
              if ($month) {
                  $output .= format_plural($month, "@count month", "@count monthes") . ' ';
              }
              if ($day) {
                  $output .= format_plural($day,   "@count day",   "@count days")    . ' ';
              }
              if ($hour) {
                  $output .= format_plural($hour,  "@counth",      "@counth")       . ' ';
              }
              if ($min) {
                  $output .= format_plural($min,   "@countmn",     "@countmn")     . ' ';
              }
              if ($sec) {
                  $output .= format_plural($sec,   "@counts",      "@counts");
              }
              break;
        }

        return $output;
    }

    /**
     * @var boolean
     */
    private $doDisplay = true;

    /**
     * @var boolean
     */
    private $withEta = false;

    /**
     * @var int
     */
    private $startTime = 0;

    /**
     * Default constructor.
     *
     * @param boolean $withEta
     *   Display ETA.
     * @param boolean $onlyVerbose
     *   Do not display the progress bar when not in verbose mode.
     */
    public function __construct($withEta = true, $onlyVerbose = false)
    {
        $this->withEta = $withEta;

        $verbose = drush_get_context('DRUSH_VERBOSE') || drush_get_context('DRUSH_DEBUG');

        // If the function is being called in quiet mode, or we're in
        // verbose/debug mode, simply return without printing anything.
        if (drush_get_context('DRUSH_QUIET') || (!$verbose && $onlyVerbose) || drush_get_context('DRUSH_BACKEND')) {
            $this->doDisplay = false;
        }
    }

    /**
     * You should call this method before starting when you intend to display
     * ETA on screen
     */
    public function start()
    {
        $this->startTime = time();
    }

    /**
     * Update the progress bar
     *
     * @param float $ratio
     */
    public function update($ratio)
    {
        // Provide a fallback if the user did not called it by himself
        if ($this->withEta && !$this->startTime) {
            $this->start();
        }

        if ($this->doDisplay) {
            $this->display($ratio);
        } else {
            $this->log($ratio);
        }
    }

    /**
     * Compute ETA from now
     */
    protected function computeEtaFromRatio($ratio)
    {
        if (!$this->startTime) {
            return;
        }

        return self::computeEta($this->startTime, $ratio);
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
        $columns    = drush_get_context('DRUSH_COLUMNS', 80);
        $output     = '';
        $suffix     = '';

        if ($this->withEta) {
            if ($eta = $this->computeEtaFromRatio($ratio)) {
                if ($eta[1]) {
                    $suffix = sprintf('%s, eta: %s',
                        self::formatDuration($eta[0], self::FORMAT_TIME_FULL),
                        self::formatDuration($eta[1], self::FORMAT_TIME_FULL)
                    );
                } else {
                    $suffix = sprintf('elapsed: %s', self::formatDuration($eta[0], self::FORMAT_TIME_FULL));
                }
            } else {
                $suffix = dt("Computing...");
            }
        }

        // Subtract 8 characters for the percentage, brackets, spaces and arrow.
        $progress_columns = $columns - 8 - strlen($suffix) - 1;
        // If ratio is 1 (complete), the > becomes a = to make a full bar.
        $arrow = ($ratio < 1) ? '>' : '=';
        // Print a new line if ratio is 1 (complete). Otherwise, use a CR.
        $line_ending = "\r";

        // Determine the current length of the progress string.
        $current_length   = floor($ratio * $progress_columns);
        $progress_string  = str_pad('', $current_length, '=');

        $output .= str_pad('', 4 - strlen($percentage)) . $percentage;
        $output .= ' [';
        $output .= $progress_string . $arrow;
        $output .= str_pad('', $progress_columns - $current_length);
        $output .= '] ';
        $output .= $suffix;
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
