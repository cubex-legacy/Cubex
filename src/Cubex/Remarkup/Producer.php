<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Remarkup;

use Cubex\Foundation\IRenderable;

/**
 * Class Produce
 * @package Cubex\Remarkup
 *
 * Example Usage
 * > Quote this text
 *
 * echo "srkh";
 * echo "brooke";
 *
 *  **bold text**
 * //italic text//
 *
 * ```
 * <?php
 * echo "srkh";
 * echo "brooke";
 * ?>
 * ```
 *
 * ##monospaced text##
 * `monospaced text`
 * ~~deleted text~~
 *
 * ``` echo "bob"; ```
 *
 *   <?php
 *   echo "srkh";
 *   echo "brooke";
 *   ?>
 *
 * = large header =
 * == smaller header ==
 * === even smaller header ===
 * ==== very small header ====
 * ===== tiny header =====
 *
 * http://www.google.com
 * https://www.google.com
 *
 * [[https://www.google.com | google]]
 * [[/fewjkh | random]]
 * [[/fewjkhfrweh]]
 *
 */
class Producer implements IRenderable
{
  public function __construct($raw, array $rules = null)
  {
    $this->_raw = $raw;
    if($rules === null)
    {
      $rules = [
        'quoted',
        'italic',
        'bold',
        'codeblocks',
        'monospaced',
        'deleted',
        'headers',
        'links',
        'linebreak',
      ];
    }
    foreach($rules as $rule)
    {
      if(method_exists($this, $rule))
      {
        $this->_raw = $this->$rule($this->_raw);
      }
    }
  }

  public function render()
  {
    return $this->_raw;
  }

  public function linebreak($text)
  {
    return str_replace("\n", "<br />\n", $text);
  }

  public function italic($text)
  {
    return preg_replace('@(?<!:)//(.+?)//@s', '<em>\1</em>', $text);
  }

  public function bold($text)
  {
    return preg_replace('@\\*\\*(.+?)\\*\\*@s', '<strong>\1</strong>', $text);
  }

  public function deleted($text)
  {
    return preg_replace('@(?<!~)~~([^\s~].*?~*)~~@s', '<del>\1</del>', $text);
  }

  public function monospaced($text)
  {
    return preg_replace_callback(
      '@##([\s\S]+?)##|\B`(.+?)`@',
      array($this, '_monospace'),
      $text
    );
  }

  protected function _monospace($matches)
  {
    $match = isset($matches[2]) ? $matches[2] : $matches[1];
    return '<tt>' . $match . '</tt>';
  }

  public function headers($text)
  {
    $func = [$this, "_processHeader"];
    $text = preg_replace_callback('/^(={1,5})(.*?)\\1?\s*$/m', $func, $text);
    return $text;
  }

  protected function _processHeader($matches)
  {
    $level = strlen($matches[1]);
    return "<h$level>" . $matches[2] . "</h$level>";
  }

  public function quoted($text)
  {
    $out = '';
    foreach(explode("\n", $text) as $line)
    {
      if(preg_match('/^>/', trim($line)))
      {
        $line = preg_replace('/^>/', '', trim($line));
        $out .= '<blockquote>' . trim($line) . '</blockquote>';
      }
      else
      {
        $out .= $line . "\n";
      }
    }
    return preg_replace('/<\/blockquote><blockquote>/', "\n", $out);
  }

  public function links($text)
  {
    $text = preg_replace_callback(
      '/\[\[([^\]]*)\]\]/',
      array($this, '_processLink'),
      $text
    );

    $text = preg_replace_callback(
      '/(^|\s)(\w{3,5}:\/\/\S+)/',
      array($this, '_processLink'),
      $text
    );

    return $text;
  }

  protected function _processLink($matches)
  {
    if(count($matches) == 2)
    {
      $prepend = '';
      $match   = $matches[1];
    }
    else
    {
      $prepend = $matches[1];
      $match   = $matches[2];
    }
    if(stristr($match, '|'))
    {
      list($link, $text) = explode("|", $match, 2);
      $link = trim($link);
      $text = trim($text);
    }
    else
    {
      $link = $text = trim($match);
    }
    return $prepend . '<a href="' . $link . '">' . $text . '</a>';
  }

  public function codeblocks($text)
  {
    $out          = '';
    $currentBlock = [];
    $withinCode   = false;
    $reqClose     = false;
    foreach(explode("\n", $text) as $line)
    {
      if(preg_match("/^(\s{2,}|```)/", $line))
      {
        if(preg_match('/^```/', $line))
        {
          $line = preg_replace('/^```\s*$/', '', substr($line, 3));
          if($reqClose || preg_match('/```$/', $line))
          {
            if(!empty($line))
            {
              $out .= $this->_parseCodeBlock([substr($line, 0, -3)]);
            }
            $reqClose = false;
            continue;
          }
          else
          {
            if(!empty($line))
            {
              $currentBlock[] = $line;
            }
            $reqClose = true;
          }
        }
        else
        {
          $currentBlock[] = substr($line, 2);
        }
        $withinCode = true;
      }
      else if($reqClose && !preg_match('/```$/', $line))
      {
        if(!empty($line))
        {
          $currentBlock[] = $line;
        }
      }
      else
      {
        if(preg_match('/```$/', $line))
        {
          $currentBlock[] = substr($line, 0, -3);
          $reqClose       = false;
        }

        if($withinCode)
        {
          $out .= $this->_parseCodeBlock($currentBlock);
          $currentBlock = [];
          $withinCode   = false;
        }
        $out .= $line . "\n";
      }
    }

    if($withinCode && $currentBlock)
    {
      $out .= $this->_parseCodeBlock($currentBlock);
    }

    return $out;
  }

  protected function _parseCodeBlock($text)
  {
    if(!empty($text))
    {
      $langCss = '';
      list($language) = sscanf($text[0], "lang=%s");
      if($language !== null)
      {
        array_shift($text);
        $langCss = ' lang-' . $language;
      }

      /**
       * http://code.google.com/p/google-code-prettify/wiki/GettingStarted
       */
      $return = '<pre class="producer-code prettyprint' . $langCss . '">';
      $return .= trim(implode("", $text)) . '</pre>';
      return $return;
    }
    return '';
  }

  public function __toString()
  {
    return (string)$this->render();
  }
}
