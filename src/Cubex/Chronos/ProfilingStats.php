<?php
/**
 * @author  Richard.Gooding
 */

namespace Cubex\Chronos;

use Cubex\Helpers\Numbers;

class ProfilingStats
{
  private $_timePrecision;

  public function __construct()
  {
    $this->_timePrecision = 3;
  }

  public function getReportData()
  {
    $includedFiles = $this->_getIncludedFiles();
    $memoryStats   = $this->_getMemoryStats();

    return [
      'Included Files'  => $includedFiles->numFiles .
      ' (total ' . Numbers::bytesToHumanReadable(
        $includedFiles->totalSize
      ) . ')',
      'Peak Memory Use' => Numbers::bytesToHumanReadable(
        $memoryStats->used
      ) . '/' .
      Numbers::bytesToHumanReadable($memoryStats->limit),
      'Total Run Time'  => Numbers::formatMicroTime(
        $this->_scriptRunTime(),
        $this->_timePrecision
      )
    ];
  }

  private function _scriptRunTime()
  {
    return microtime(true) - PHP_START;
  }

  private function _getIncludedFiles()
  {
    $result            = new \stdClass();
    $result->numFiles  = 0;
    $result->totalSize = 0;
    $result->files     = [];

    $files = get_included_files();
    foreach($files as $file)
    {
      $result->numFiles++;
      $fileInfo           = new \stdClass();
      $fileInfo->filename = $file;
      if(file_exists($file))
      {
        $size           = filesize($file);
        $fileInfo->size = $size;
        $result->totalSize += $size;
      }
      else
      {
        $fileInfo->size = 'unknown';
      }
      $result->files[] = $fileInfo;
    }

    return $result;
  }

  private function _getMemoryStats()
  {
    $stats       = new \stdClass();
    $stats->used = memory_get_peak_usage();

    // get the memory limit in bytes
    $limit    = trim(ini_get('memory_limit'));
    $intLimit = intval($limit);
    switch(strtolower(substr($limit, -1)))
    {
      case 'g':
        $intLimit *= 1024;
      case 'm':
        $intLimit *= 1024;
      case 'k':
        $intLimit *= 1024;
    }

    $stats->limit = $intLimit;
    return $stats;
  }
}
