<?php
/**
 * @author Brooke Bryan
 */
//TODO: Relocate into Views namespace
namespace Cubex\Foundation;

/**
 * An object that can be rendered with __toString() or with render()
 */
interface IRenderable
{
  /**
   * @return string
   */
  public function render();

  public function __toString();
}
