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

  public function validatorExists($identify)
  {
    if(isset($this->_validators[$identify]))
    {
      return true;
    }
    else
    {
      foreach($this->_validators as $validator)
      {
        if($identify === $validator['validator'])
        {
          return true;
        }
      }
    }
    return false;
  }

  public function setValidators($validators)
  {
    $this->_validators = $validators;
    return $this;
  }

  public function getValidators()
  {
    return $this->_validators;
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
    $result                  = true;

    if($value === null || empty($value))
    {
      if(method_exists($this, 'required'))
      {
        if($this->required())
        {
          $this->_validationErrors[] = 'Required';
          $result                    = false;
        }
      }
    }

    foreach($this->_validators as $validatable)
    {
      $validator = $validatable['validator'];
      $options   = (array)$validatable['options'];
      $valid     = true;

      if(is_callable($validator))
      {
        try
        {
          $params = $options;
          array_unshift($params, $value);
          $valid = call_user_func_array($validator, $params);
          if(!$valid)
          {
            $result = false;
          }
        }
        catch(\Exception $e)
        {
          $this->_validationErrors[] = $e->getMessage();
          $result                    = false;
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

      if($validator instanceof IValidator)
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

      if(!$valid)
      {
        $result = false;
      }
    }
    return $result;
  }

  public function validationErrors()
  {
    return $this->_validationErrors;
  }
}
