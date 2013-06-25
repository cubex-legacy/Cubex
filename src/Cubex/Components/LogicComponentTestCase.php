<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Components;

use Cubex\Foundation\Tests\CubexTestCase;

class LogicComponentTestCase extends CubexTestCase
{
  public function setUp()
  {
    TestLogicComponent::bindServiceManager();
  }
}
