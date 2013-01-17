<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex\View;

use Cubex\Dispatch\Utils\RequireTrait;
use Cubex\Events\EventManager;
use Cubex\Foundation\Renderable;
use Cubex\I18n\Translatable;
use Cubex\I18n\TranslateTraits;

abstract class ViewModel implements Renderable, Translatable
{
  use TranslateTraits;
  use RequireTrait;

  abstract public function render();

  public function __toString()
  {
    try
    {
      return $this->render();
    }
    catch(\Exception $e)
    {
      return $e->getMessage();
    }
  }

  /**
   * Attempt to set page title
   *
   * @param string $title
   *
   * @return static
   */
  public function setTitle($title = '')
  {
    EventManager::trigger(
      EventManager::CUBEX_PAGE_TITLE, ['title' => $title], $this
    );
    return $this;
  }
}
