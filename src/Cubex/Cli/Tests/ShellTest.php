<?php
/**
 * @author gareth.evans
 */

namespace Cubex\Cli\Tests;

use Cubex\Cli\Shell;
use Cubex\Tests\TestCase;

class ShellTest extends TestCase
{
  private $_server;

  public function setUp()
  {
    $this->_server = $_SERVER;
    unset($_SERVER);
  }

  public function tearDown()
  {
    $_SERVER = $this->_server;
  }

  public function testSupportsColor()
  {
    $this->assertTrue(Shell::supportsColor());

    $_SERVER["TERM"] = "cygwin";
    $this->assertFalse(Shell::supportsColor());
    unset($_SERVER);

    $_SERVER["PROMPT"] = "\$P\$G";
    $this->assertFalse(Shell::supportsColor());
    unset($_SERVER);
  }

  public function testSet()
  {
    $this->assertClassHasStaticAttribute(
      "_foregroundColour", "\\Cubex\\Cli\\Shell"
    );
    $this->assertClassHasStaticAttribute(
      "_backgroundColour", "\\Cubex\\Cli\\Shell"
    );

    Shell::setForeground(Shell::COLOUR_FOREGROUND_RED);
    $this->assertAttributeEquals(
      Shell::COLOUR_FOREGROUND_RED, "_foregroundColour", "\\Cubex\\Cli\\Shell"
    );
    Shell::setForeground(Shell::COLOUR_BACKGROUND_BLACK);
    $this->assertAttributeEquals(
      Shell::COLOUR_BACKGROUND_BLACK, "_foregroundColour", "\\Cubex\\Cli\\Shell"
    );

    Shell::setBackground(Shell::COLOUR_BACKGROUND_RED);
    $this->assertAttributeEquals(
      Shell::COLOUR_BACKGROUND_RED, "_backgroundColour", "\\Cubex\\Cli\\Shell"
    );
    Shell::setBackground(Shell::COLOUR_BACKGROUND_BLACK);
    $this->assertAttributeEquals(
      Shell::COLOUR_BACKGROUND_BLACK, "_backgroundColour", "\\Cubex\\Cli\\Shell"
    );

    Shell::clearForeground();
    Shell::clearBackground();
    $this->assertAttributeEquals(
      null, "_foregroundColour", "\\Cubex\\Cli\\Shell"
    );
    $this->assertAttributeEquals(
      null, "_backgroundColour", "\\Cubex\\Cli\\Shell"
    );
  }

  public function testReturnsOfSlightlyUntestableMethods()
  {
    $this->assertTrue(is_int(Shell::columns()));
    $this->assertTrue(is_bool(Shell::isPiped()));
  }

  public function testColouredText()
  {
    $_SERVER["TERM"] = "cygwin";
    $this->assertEquals("text", Shell::colouredText("text"));
    unset($_SERVER);

    $excpectedDelimiter = "m";
    $excpectedPreColor  = "\033[";

    Shell::setForeground(Shell::COLOUR_FOREGROUND_BLACK);
    $excpected = $excpectedPreColor;
    $excpected .= Shell::COLOUR_FOREGROUND_BLACK;
    $excpected .= $excpectedDelimiter;
    $excpected .= "foreground.text";
    $excpected .= $excpectedPreColor;
    $excpected .= "0";
    $excpected .= $excpectedDelimiter;

    $this->assertEquals($excpected, Shell::colouredText("foreground.text"));
    Shell::clearForeground();

    Shell::setBackground(Shell::COLOUR_BACKGROUND_BLACK);
    $excpected = $excpectedPreColor;
    $excpected .= Shell::COLOUR_BACKGROUND_BLACK;
    $excpected .= $excpectedDelimiter;
    $excpected .= "background.text";
    $excpected .= $excpectedPreColor;
    $excpected .= "0";
    $excpected .= $excpectedDelimiter;

    $this->assertEquals($excpected, Shell::colouredText("background.text"));
    Shell::clearBackground();

    Shell::setForeground(Shell::COLOUR_FOREGROUND_BLACK);
    Shell::setBackground(Shell::COLOUR_BACKGROUND_BLACK);
    $excpected = $excpectedPreColor;
    $excpected .= Shell::COLOUR_FOREGROUND_BLACK;
    $excpected .= $excpectedDelimiter;
    $excpected .= $excpectedPreColor;
    $excpected .= Shell::COLOUR_BACKGROUND_BLACK;
    $excpected .= $excpectedDelimiter;
    $excpected .= "foreground.background.text";
    $excpected .= $excpectedPreColor;
    $excpected .= "0";
    $excpected .= $excpectedDelimiter;

    $this->assertEquals(
      $excpected, Shell::colouredText("foreground.background.text")
    );
    Shell::clearForeground();
    Shell::clearBackground();
  }

  public function testColourText()
  {
    $_SERVER["TERM"] = "cygwin";
    $this->assertEquals("text", Shell::colourText("text"));
    Shell::setForeground(Shell::COLOUR_FOREGROUND_BLACK);
    $this->assertEquals("text", Shell::colourText("text"));
    unset($_SERVER);

    $excpectedDelimiter = "m";
    $excpectedPreColor  = "\033[";

    $excpected = $excpectedPreColor;
    $excpected .= Shell::COLOUR_FOREGROUND_BLACK;
    $excpected .= $excpectedDelimiter;
    $excpected .= $excpectedPreColor;
    $excpected .= Shell::COLOUR_BACKGROUND_BLACK;
    $excpected .= $excpectedDelimiter;
    $excpected .= "foreground.background.text";
    $excpected .= $excpectedPreColor;
    $excpected .= "0";
    $excpected .= $excpectedDelimiter;

    $this->assertEquals(
      $excpected,
      Shell::colourText(
        "foreground.background.text",
        Shell::COLOUR_FOREGROUND_BLACK,
        Shell::COLOUR_BACKGROUND_BLACK
      )
    );

    Shell::setForeground(Shell::COLOUR_FOREGROUND_BLUE);
    Shell::setBackground(Shell::COLOUR_BACKGROUND_BLUE);

    $this->assertEquals(
      $excpected,
      Shell::colourText(
        "foreground.background.text",
        Shell::COLOUR_FOREGROUND_BLACK,
        Shell::COLOUR_BACKGROUND_BLACK
      )
    );

    Shell::clearForeground();
    Shell::clearBackground();
  }
}
