<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Log\Tests;

use Cubex\Foundation\Tests\CubexTestCase;
use Psr\Log\LogLevel;

class LoggerTest extends CubexTestCase
{
  public function testCorrectLevelPassed()
  {
    $logger = new Logger();

    $logger->emergency("emergency");
    $this->assertEquals(LogLevel::EMERGENCY, Logger::$logArguments[0]);

    $logger->alert("alert");
    $this->assertEquals(LogLevel::ALERT, Logger::$logArguments[0]);

    $logger->critical("critical");
    $this->assertEquals(LogLevel::CRITICAL, Logger::$logArguments[0]);

    $logger->error("error");
    $this->assertEquals(LogLevel::ERROR, Logger::$logArguments[0]);

    $logger->warning("warning");
    $this->assertEquals(LogLevel::WARNING, Logger::$logArguments[0]);

    $logger->notice("notice");
    $this->assertEquals(LogLevel::NOTICE, Logger::$logArguments[0]);

    $logger->info("info");
    $this->assertEquals(LogLevel::INFO, Logger::$logArguments[0]);

    $logger->debug("debug");
    $this->assertEquals(LogLevel::DEBUG, Logger::$logArguments[0]);
  }
}
