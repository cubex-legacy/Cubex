<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Type\Tests;

use Cubex\Tests\TestCase;
use Cubex\Type\Tests\Type\Bool;
use Cubex\Type\Tests\Type\EnumNoConstants;
use Cubex\Type\Tests\Type\EnumNoDefault;

class EnumTest extends TestCase
{
  public function testSomething()
  {
    $this->setExpectedException('UnexpectedValueException');

    new Bool('non_value');
  }

  public function testSetAndToString()
  {
    $enum = new Bool(Bool::TRUE);
    $this->assertEquals($enum, Bool::TRUE);
  }

  public function testExcptionThrownWhenNoDefaultSet()
  {
    $this->setExpectedException('UnexpectedValueException');

    new EnumNoDefault();
  }

  public function testExcptionThrownWhenNoConstantsSet()
  {
    $this->setExpectedException('UnexpectedValueException');

    new EnumNoConstants();
  }

  public function testDefaultSetWhenNoValuePassed()
  {
    $enum = new Bool();
    $this->assertEquals($enum, Bool::__default);
  }
}
