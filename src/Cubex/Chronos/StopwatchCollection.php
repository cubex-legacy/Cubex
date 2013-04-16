<?php
/**
 * @author  Richard.Gooding
 */

namespace Cubex\Chronos;

use Cubex\Cli\Shell;
use Cubex\Helpers\Numbers;

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
    $report = [
      'Total Run Time' =>
      Numbers::formatMicroTime($this->scriptRunTime(), $this->_precision)
    ];

    if(count($this->_stopwatches) > 0)
    {
      foreach($this->_stopwatches as $sw)
      {
        $report[$sw->getName()] =
        Numbers::formatMicroTime($sw->getTime(), $this->_precision);
      }
    }

    return $report;
  }
}
