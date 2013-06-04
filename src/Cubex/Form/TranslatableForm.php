<?php
/**
 * @author gareth.evans
 */

namespace Cubex\Form;

use Cubex\I18n\ITranslatable;
use Cubex\I18n\TranslateTraits;

abstract class TranslatableForm extends Form implements ITranslatable
{
  use TranslateTraits;
}
