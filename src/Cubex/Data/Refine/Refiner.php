<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Data\Refine;

class Refiner
{
  /**
   * @var IRefinement[]
   */
  protected $_refinements;
  protected $_raw;
  protected $_matchAll;

  public function __construct(
    array $raw, array $refinements = [], $matchAll = true
  )
  {
    $this->_raw      = $raw;
    $this->_matchAll = $matchAll;
    $this->addRefinements($refinements);
  }

  public function setMatchAll($matchAll = true)
  {
    $this->_matchAll = $matchAll;
    return $this;
  }

  public function addRefinement(IRefinement $refinement)
  {
    $this->_refinements[] = $refinement;
    return $this;
  }

  public function addRefinements(array $refinements)
  {
    foreach($refinements as $refine)
    {
      if($refine instanceof IRefinement)
      {
        $this->addRefinement($refine);
      }
    }
    return $this;
  }

  public function refine()
  {
    foreach($this->_raw as $itemId => $entry)
    {
      $passed = false;
      foreach($this->_refinements as $refine)
      {
        if($refine->verify($entry))
        {
          $passed = true;
        }
        else if($this->_matchAll)
        {
          $passed = false;
          break;
        }
      }

      if(!$passed)
      {
        unset($this->_raw[$itemId]);
      }
    }

    return $this->_raw;
  }
}
