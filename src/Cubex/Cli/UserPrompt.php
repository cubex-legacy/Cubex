<?php
/**
 * @author  Richard.Gooding
 */

namespace Cubex\Cli;

use Cubex\Data\Filter\Filterable;
use Cubex\Data\Filter\FilterableTrait;
use Cubex\Data\Validator\Validatable;
use Cubex\Data\Validator\ValidatableTrait;

class UserPrompt implements Validatable, Filterable
{
  use ValidatableTrait;
  use FilterableTrait;

  private $_prompt;
  private $_default;

  /**
   * Prompt the user for a string
   *
   * @param string $prompt  The prompt to show to the user
   * @param string $default The default value if the user just presses enter
   *
   * @return mixed
   */
  public static function prompt($prompt, $default = "")
  {
    $p = new static($prompt, $default);
    return $p->show();
  }

  public static function confirm($prompt, $default = "")
  {
    $default = strtolower($default);
    if(($default != 'y') && ($default != 'n') && ($default != ''))
    {
      throw new \Exception('Invalid default value for confirm: ' . $default);
    }

    $prompt .= ' (';
    $prompt .= $default == 'y' ? 'Y' : 'y';
    $prompt .= '/';
    $prompt .= $default == 'n' ? 'N' : 'n';
    $prompt .= ')';

    $p = new static($prompt, $default);
    $p->addFilter(
      function ($value)
      {
        return strtolower(trim($value));
      }
    );
    $p->addValidator(
      function ($value)
      {
        return in_array(strtolower($value), ['y', 'n']);
      }
    );

    return $p->show() == 'y';
  }

  public function __construct($prompt, $default = "")
  {
    $this->_prompt  = $prompt;
    $this->_default = $default;

    if($default)
    {
      $this->_prompt .= ' [' . $default . ']';
    }
    $this->_prompt .= ' ';
  }

  public function show()
  {
    do
    {
      $input = $this->_doPrompt();
      $input = $this->filter($input);
    }
    while(!$this->_checkValidation($input));

    return $input;
  }

  protected function _checkValidation($data)
  {
    $res = $this->isValid($data);
    if(!$res)
    {
      foreach($this->validationErrors() as $error)
      {
        echo " " . $error . "\n";
      }
    }
    return $res;
  }

  protected function _doPrompt()
  {
    echo $this->_prompt;
    $input = rtrim(fgets(STDIN), "\r\n");

    if(($input == "") && ($this->_default != ""))
    {
      $input = $this->_default;
    }

    return $input;
  }
}
