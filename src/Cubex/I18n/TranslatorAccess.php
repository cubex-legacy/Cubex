<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex\I18n;

interface TranslatorAccess
{
  /**
   * @return \Cubex\I18n\Loader\ITanslationLoader
   */
  public function getTranslator();
}
