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
  protected $_refinements = [];
  protected $_raw = [];

  public function __construct(array $raw, array $refinements = [])
  {
    $this->_raw = $raw;
    $this->addRefinements($refinements);
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
    foreach($this->_refinements as $refine)
    {
      foreach($this->_raw as $itemId => $entry)
      {
        if(!$refine->verify($entry))
        {
          unset($this->_raw[$itemId]);
        }
      }
    }

    return $this->_raw;
  }
}
