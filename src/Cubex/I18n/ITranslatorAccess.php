<?php
/**
 * @author  brooke.bryan
 */
namespace Cubex\I18n;

interface ITranslatorAccess
{
  /**
   * @return \Cubex\I18n\Loader\ITanslationLoader
   */
  public function getTranslator();
}
