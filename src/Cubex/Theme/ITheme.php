<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Theme;

interface ITheme
{
  public function getTemplate($template = 'index');

  public function getLayout($layout = 'default');

  public function initiate();

  public function getIniFileDirectory();
}
