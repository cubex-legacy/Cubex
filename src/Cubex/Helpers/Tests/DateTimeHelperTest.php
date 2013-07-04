<?php
/**
 * @author gareth.evans
 */

namespace Cubex\Helpers\Tests;

use Cubex\Foundation\Tests\CubexTestCase;
use Cubex\Helpers\DateTimeHelper;

class DateTimeHelperTest extends CubexTestCase
{
  private $_tz;

  public function setUp()
  {
    $this->_tz = date_default_timezone_get();
    date_default_timezone_set("Europe/London");
  }

  public function tearDown()
  {
    date_default_timezone_set($this->_tz);
  }

  /**
   * @dataProvider dateTimeProvider
   */
  public function testDateTimeFromAnything($input, $ouput)
  {
    $this->assertEquals($ouput, DateTimeHelper::dateTimeFromAnything($input));
  }

  public function testDateTimeFromAnythingException()
  {
    $this->setExpectedException(
      "InvalidArgumentException",
      "Failed Converting param of type 'string' to DateTime object"
    );

    DateTimeHelper::dateTimeFromAnything("foobar");
  }

  /**
   * @dataProvider dateFormatProvider
   */
  public function testFormattedDateFromAnything($input, $format, $output)
  {
    $this->assertEquals(
      $output,
      DateTimeHelper::formattedDateFromAnything($input, $format)
    );
  }

  public function dateTimeProvider()
  {
    return [
      [1372934760, (new \DateTime())->setTimestamp(1372934760)],
      ["1372934760", (new \DateTime())->setTimestamp("1372934760")],
      ["01372934760", (new \DateTime())->setTimestamp((int)"01372934760")],
      ["13-05-12", new \DateTime("13-05-12")],
      [
        (new \DateTime())->setTimestamp(strtotime("13-05-12")),
        (new \DateTime())->setTimestamp(strtotime("13-05-12"))
      ],
      ["+4 days", (new \DateTime())->setTimestamp(strtotime("+4 days"))],
    ];
  }

  public function dateFormatProvider()
  {
    return [
      [1372934760, 'Y-m-d', "2013-07-04"],
      [
        "1372934760",
        'l jS \of F Y h:i:s A',
        "Thursday 4th of July 2013 11:46:00 AM"
      ],
      ["13-05-12", 'l \t\h\e jS', "Sunday the 12th"],
      [
        (new \DateTime())->setTimestamp(strtotime("13-05-12")),
        'D M j G:i:s T Y',
        "Sun May 12 0:00:00 BST 2013"
      ],
      ["+4 days", 'Y-m-d H:i:s', date('Y-m-d H:i:s', strtotime("+4 days"))],
    ];
  }
}
