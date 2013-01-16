<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Data\Validator;

trait ValidatableTrait
{
  protected $_validators = [];
  protected $_validationErrors = [];

  public function addValidator($validator, array $options = [], $alias = null)
  {
    $append = array('validator' => $validator, 'options' => $options);

    if($alias === null)
    {
      $this->_validators[] = $append;
    }
    else
    {
      $this->_validators[$alias] = $append;
    }
    return $this;
  }

  public function removeValidatorByAlias($alias)
  {
    unset($this->_validators[$alias]);
    return $this;
  }

  public function removeValidator($validator)
  {
    $key = array_search($validator, $this->_validators);
    if($key !== null)
    {
      unset($this->_validators[$key]);
    }
    return $this;
  }

  public function removeAllValidators()
  {
    $this->_validators = [];
    return $this;
  }

  public function isValid($value)
  {
    $this->_validationErrors = [];
    $valid                   = true;

    foreach($this->_validators as $validatable)
    {
      $validator = $validatable['validator'];
      $options   = (array)$validatable['options'];

      if(is_callable($validator))
      {
        try
        {
          $params = array_unshift($options, $value);
          $valid  = call_user_func($validator, $params);
        }
        catch(\Exception $e)
        {
          $this->_validationErrors[] = $e->getMessage();
        }
        continue;
      }
      else if(is_scalar($validator))
      {
        if(class_exists($validator))
        {
          $validator = new $validator();
        }
      }

      if($validator instanceof ValidatorInterface)
      {
        $validator->setOptions($options);
        $valid  = $validator->isValid($value);
        $errors = $validator->errorMessages();
        if(!empty($errors))
        {
          $this->_validationErrors = array_merge(
            $this->_validationErrors, $errors
          );
        }
      }
    }
    return $valid;
  }

  public function validationErrors()
  {
    return $this->_validationErrors;
  }
}
