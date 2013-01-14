<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex\I18n;

interface TranslatorAccess
{
  /**
   * @return \Cubex\I18n\Loader\Loader
   */
  public function getTranslator();
}
