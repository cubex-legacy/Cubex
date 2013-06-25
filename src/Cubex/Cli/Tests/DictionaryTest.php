<?php
/**
 * @author gareth.evans
 */

namespace Cubex\Cli\Tests;

use Cubex\Cli\Dictionary;
use Cubex\Foundation\Config\ConfigGroup;
use Cubex\Foundation\Tests\CubexTestCase;

class DictionaryTest extends CubexTestCase
{
  private $_tasksArr = [
    "task1" => "class1",
    "task2" => "class2",
    "task3" => "class3",
    "task4" => "class4"
  ];

  public function testConstructSetsDefaults()
  {
    $dictionary = new Dictionary();
    $defaults   = $dictionary->defaultTasks();

    foreach($defaults as $task => $map)
    {
      $this->assertEquals($map, $dictionary->match($task));
    }
  }

  public function testAddTask()
  {
    $dictionary = new Dictionary();
    $dictionary->addTask("task1", "class1");

    $this->assertEquals("class1", $dictionary->match("task1"));
  }

  public function testAddTasks()
  {
    $dictionary = new Dictionary();
    $dictionary->addTasks($this->_tasksArr);

    foreach($this->_tasksArr as $task => $map)
    {
      $this->assertEquals($map, $dictionary->match($task));
    }
  }

  public function testMatchFallback()
  {
    $dictionary = new Dictionary();

    $this->assertEquals("task1", $dictionary->match("task1"));
    $this->assertEquals("path\\to\\task1", $dictionary->match("path.to.task1"));
  }

  public function testAddTasksFromConfig()
  {
    $configGroup = ConfigGroup::fromArray(
      ["cli_dictionary" => $this->_tasksArr]
    );

    $dictionary = new Dictionary();
    $dictionary->configure($configGroup);

    foreach($this->_tasksArr as $task => $map)
    {
      $this->assertEquals($map, $dictionary->match($task));
    }
  }
}
