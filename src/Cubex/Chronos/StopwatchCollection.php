<?php
/**
 * @author  Richard.Gooding
 */

namespace Cubex\Chronos;

use Cubex\Cli\Shell;
use Cubex\Helpers\Numbers;
use Cubex\Text\TextTable;

class StopwatchCollection
{
  /**
   * @var Stopwatch[]
   */
  private $_stopwatches;
  /**
   * @var int
   */
  private $_precision;

  public function __construct()
  {
    $this->_stopwatches = [];
    $this->_precision   = 3;
  }

  /**
   * @param string $name
   *
   * @return Stopwatch
   */
  public function newStopwatch($name)
  {
    $sw = new Stopwatch($name);

    $this->_stopwatches[] = $sw;
    return $sw;
  }

  /**
   * @param string $name
   * @param string $startEvent
   * @param string $stopEvent
   *
   * @return EventStopwatch
   */
  public function newEventStopwatch($name, $startEvent, $stopEvent)
  {
    $sw = new EventStopwatch($name, $startEvent, $stopEvent);

    $this->_stopwatches[] = $sw;
    return $sw;
  }

  public function scriptRunTime()
  {
    return microtime(true) - PHP_START;
  }

  public function getReportData()
  {
    $totalTime = $this->scriptRunTime();

    $report = [
      [
        'Total Run Time',
        Numbers::formatMicroTime($totalTime, $this->_precision)
      ]
    ];

    if(count($this->_stopwatches) > 0)
    {
      foreach($this->_stopwatches as $sw)
      {
        $swTotal = $sw->totalTime();
        $percent = ($swTotal * 100) / $totalTime;

        $report[] = [
          $sw->getName(),
          Numbers::formatMicroTime(max($sw->minTime(), 0), $this->_precision),
          Numbers::formatMicroTime($sw->maxTime(), $this->_precision),
          Numbers::formatMicroTime($sw->averageTime(), $this->_precision),
          Numbers::formatMicroTime($swTotal, $this->_precision),
          sprintf("%.1f%%", $percent)
        ];
      }
    }

    return $report;
  }

  public function displayReport()
  {
    $t = new TextTable();
    $t->setColumnHeaders('Name', 'Min', 'Max', 'Avg', 'Total', '%');
    $t->appendRows($this->getReportData());
    echo $t;
  }
}
