<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Theme;

interface ITheme
{
  public function getTemplate($template = 'default');

  public function initiate();
}
