<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex\View;

use Cubex\Container\Container;
use Cubex\Dispatch\Utils\RequireTrait;
use Cubex\Events\EventManager;
use Cubex\Foundation\DataHandler\HandlerTrait;
use Cubex\Foundation\IRenderable;
use Cubex\I18n\ITranslatable;
use Cubex\I18n\TranslateTraits;

abstract class ViewModel implements IRenderable, ITranslatable
{
  use TranslateTraits;
  use RequireTrait;
  use HandlerTrait;

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

  public function request()
  {
    return Container::request();
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
      EventManager::CUBEX_PAGE_TITLE,
      ['title' => $title],
      $this
    );
    return $this;
  }
}
