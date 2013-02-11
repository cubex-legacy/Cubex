<?php
/**
 * @author gareth.evans
 */

namespace Cubex\I18n;

use Cubex\ServiceManager\Service;

interface LocaleService extends Service
{
  public function getLocale();
}
