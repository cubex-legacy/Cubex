<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Mapper;

/**
 * Group together mappers to save as a group
 */
class MapperGroup
{
  /**
   * @var DataMapper[]
   */
  protected $_mappers;
  /**
   * @var MapperGroup[]
   */
  protected $_subGroups;

  protected $_ignoreExceptions = false;
  protected $_processSubGroupOnFailed = false;

  public function addSubGroup(MapperGroup $group)
  {
    $this->_subGroups[] = $group;
    return $this;
  }

  public function clearSubGroups()
  {
    $this->_subGroups = [];
    return $this;
  }

  public function getSubGroups()
  {
    return $this->_subGroups;
  }

  public function addMapper(DataMapper $mapper)
  {
    $this->_mappers[] = $mapper;
    return $this;
  }

  public function clearMappers()
  {
    $this->_mappers = [];
    return $this;
  }

  public function getMappers()
  {
    return $this->_mappers;
  }

  public function getIgnoreExceptions()
  {
    return $this->_ignoreExceptions;
  }

  /**
   * Should exceptions from validation be ignored
   *
   * @param bool $process
   *
   * @return $this
   */
  public function setIgnoreExceptions($process = true)
  {
    $this->_ignoreExceptions = (bool)$process;
    return $this;
  }

  public function getProcessSubGroupOnFailed()
  {
    return $this->_processSubGroupOnFailed;
  }

  /**
   * Should sub groups attempt to save on failure
   *
   * @param bool $process
   *
   * @return $this
   */
  public function setProcessSubGroupOnFailed($process = true)
  {
    $this->_processSubGroupOnFailed = (bool)$process;
    return $this;
  }

  public function saveChanges($validate = false)
  {
    return $this->_process(__FUNCTION__, [$validate]);
  }

  public function delete()
  {
    return $this->_process(__FUNCTION__);
  }

  public function count()
  {
    return count($this->_mappers);
  }

  protected function _process($method, $args = null)
  {
    $failed = false;
    foreach($this->_mappers as $mapper)
    {
      try
      {
        if($args === null)
        {
          $mapper->$method();
        }
        else
        {
          call_user_func_array([$mapper, $method], $args);
        }
      }
      catch(\Exception $e)
      {
        if($this->_ignoreExceptions)
        {
          $failed = true;
        }
        else
        {
          throw $e;
        }
      }
    }

    if(!$failed || $this->_processSubGroupOnFailed)
    {
      foreach($this->_subGroups as $group)
      {
        try
        {
          if($args === null)
          {
            $group->$method();
          }
          else
          {
            call_user_func_array([$group, $method], $args);
          }
        }
        catch(\Exception $e)
        {
          if($this->_ignoreExceptions)
          {
            $failed = true;
          }
          else
          {
            throw $e;
          }
        }
      }
    }

    return !$failed;
  }
}
