<?php
/**
 * @author gareth.evans
 */

namespace Cubex\I18n;

use Cubex\ServiceManager\IService;

interface ILocaleService extends IService
{
  public function getLocale();
}
