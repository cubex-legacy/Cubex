<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Helpers;

class Inflection
{
  protected $_word;
  protected $_lowerWord;
  protected $_pluralise = true;

  protected $_completed = false;
  protected $_final;

  protected function _complete($word = null)
  {
    $this->_completed = true;
    if($word !== null)
    {
      $this->_final = $word;
    }
    return true;
  }

  public static function isVowel($letter)
  {
    return in_array(strtolower($letter), ['a', 'e', 'i', 'o', 'u']);
  }

  public static function singularise($word)
  {
    $instance             = new self;
    $instance->_word      = $word;
    $instance->_final     = $word;
    $instance->_lowerWord = strtolower($word);
    $instance->_pluralise = false;

    $rules = [
      'nonConversions',
      'fullConversion',
      'endsWithXZCHSH',
      'endsWithIes',
      'endsWithVes',
      'endsWithEs',
      'endsWithFs',
    ];
    foreach($rules as $rule)
    {
      if($instance->$rule())
      {
        return $instance->_final;
      }
    }

    if(ends_with($instance->_final, 's'))
    {
      return substr($instance->_final, 0, -1);
    }
    else
    {
      return $instance->_final;
    }
  }

  public static function pluralise($word)
  {
    $instance             = new self;
    $instance->_word      = $word;
    $instance->_final     = $word;
    $instance->_lowerWord = strtolower($word);
    $instance->_pluralise = true;

    $rules = [
      'nonConversions',
      'fullConversion',
      'endsWithO',
      'endsWithY',
      'endsWithIfe',
      'endsWithF',
      'endsWithSXZCHSH',
    ];
    foreach($rules as $rule)
    {
      if($instance->$rule())
      {
        return $instance->_final;
      }
    }

    return $instance->_final . 's';
  }

  public function nonConversions()
  {
    $nonConversions = [
      'news',
      'data',
      'bison',
      'buffalo',
      'deer',
      'fish',
      'moose',
      'pike',
      'sheep',
      'salmon',
      'trout',
      'swine',
      'plankton',
      'money',
      'information',
      'series',
      'equipment',
    ];

    if(in_array($this->_lowerWord, $nonConversions))
    {
      return $this->_complete();
    }
    return false;
  }

  public function fullConversion()
  {
    $irregular = [
      'person'   => 'people',
      'child'    => 'children',
      'ox'       => 'oxen',
      'foot'     => 'feet',
      'tooth'    => 'teeth',
      'goose'    => 'geese',
      'mouse'    => 'mice',
      'louse'    => 'lice',
      'man'      => 'men',
      'woman'    => 'women',
      'index'    => 'indices',
      'analysis' => 'analyses',
    ];

    foreach($irregular as $singular => $plural)
    {
      if($this->_pluralise)
      {
        if($this->_lowerWord == $singular)
        {
          return $this->_complete($plural);
        }
      }
      else
      {
        if($this->_lowerWord == $plural)
        {
          return $this->_complete($singular);
        }
      }
    }
    return false;
  }

  public function endsWithO()
  {
    if(ends_with($this->_lowerWord, 'o'))
    {
      if(in_array(
        $this->_lowerWord,
        [
        'piano',
        'zero',
        'photo',
        'pro',
        'radio',
        'disco',
        'logo',
        ]
      )
      )
      {
        return $this->_complete($this->_word . 's');
      }

      if(!static::isVowel(substr($this->_lowerWord, -2, 1)))
      {
        return $this->_complete($this->_word . 'es');
      }
    }
    return false;
  }

  public function endsWithY()
  {
    if(ends_with($this->_lowerWord, 'y'))
    {
      if(in_array($this->_lowerWord, ['day', 'monkey']))
      {
        return $this->_complete($this->_word . 's');
      }

      if(!static::isVowel(substr($this->_lowerWord, -2, 1)))
      {
        return $this->_complete(substr($this->_word, 0, -1) . 'ies');
      }
    }
    return false;
  }

  public function endsWithIfe()
  {
    if(ends_with($this->_lowerWord, 'ife'))
    {
      return $this->_complete(substr($this->_word, 0, -2) . 'ves');
    }
    return false;
  }

  protected function _fWords()
  {
    return ['chef', 'cliff', 'ref', 'roof'];
  }

  public function endsWithF()
  {
    if(ends_with($this->_lowerWord, 'f'))
    {
      if(in_array($this->_lowerWord, $this->_fWords()))
      {
        return $this->_complete($this->_word . 's');
      }
      return $this->_complete(substr($this->_word, 0, -1) . 'ves');
    }
    return false;
  }

  public function endsWithSXZCHSH()
  {
    if(
      ends_with($this->_lowerWord, 's')
      || ends_with($this->_lowerWord, 'x')
      || ends_with($this->_lowerWord, 'z')
      || ends_with($this->_lowerWord, 'ch')
      || ends_with($this->_lowerWord, 'sh')
    )
    {
      return $this->_complete($this->_word . 'es');
    }
    return false;
  }

  public function endsWithXZCHSH()
  {
    if(
      ends_with($this->_lowerWord, 'ses')
      || ends_with($this->_lowerWord, 'xes')
      || ends_with($this->_lowerWord, 'zes')
      || ends_with($this->_lowerWord, 'ches')
      || ends_with($this->_lowerWord, 'shes')
    )
    {
      if(in_array($this->_lowerWord, ['houses']))
      {
        return $this->_complete(substr($this->_word, 0, -1));
      }
      else
      {
        return $this->_complete(substr($this->_word, 0, -2));
      }
    }
    return false;
  }

  public function endsWithIes()
  {
    if(ends_with($this->_lowerWord, 'ovies'))
    {
      return $this->_complete(substr($this->_word, 0, -1));
    }
    else if(ends_with($this->_lowerWord, 'ies'))
    {
      return $this->_complete(substr($this->_word, 0, -3) . 'y');
    }
    return false;
  }

  public function endsWithVes()
  {
    if(ends_with($this->_lowerWord, 'ives'))
    {
      return $this->_complete(substr($this->_word, 0, -3) . 'fe');
    }
    else if(ends_with($this->_lowerWord, 'ves'))
    {
      return $this->_complete(substr($this->_word, 0, -3) . 'f');
    }
    return false;
  }

  public function endsWithEs()
  {
    if(in_array($this->_lowerWord, ['shoes']))
    {
      return $this->_complete(substr($this->_word, 0, -1));
    }
    else if(ends_with($this->_lowerWord, 'oes'))
    {
      return $this->_complete(substr($this->_word, 0, -2));
    }
    else if(ends_with($this->_lowerWord, 'es'))
    {
      return $this->_complete(substr($this->_word, 0, -1));
    }
    return false;
  }

  public function endsWithFs()
  {
    if(in_array(substr($this->_lowerWord, 0, -1), $this->_fWords()))
    {
      return $this->_complete(substr($this->_word, 0, -1));
    }
    return false;
  }
}
