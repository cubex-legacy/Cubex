<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Data\DocBlock;

use Cubex\Helpers\Strings;

class DocBlockParser
{
  protected $_docBlock;
  protected $_object;

  public function __construct(IDocBlockAware $object, $docBlock = null)
  {
    if($docBlock === null)
    {
      $reflect  = new \ReflectionObject($object);
      $docBlock = $reflect->getDocComment();
    }
    $this->_docBlock = $docBlock;
    $this->_object   = $object;
  }

  public function parse()
  {
    $docLines = Strings::docCommentLines($this->_docBlock);
    $comment  = [];
    foreach($docLines as $docLine)
    {
      if(starts_with($docLine, '@'))
      {
        if(!starts_with($docLine, '@comment'))
        {
          if(strstr($docLine, ' '))
          {
            list($type, $detail) = explode(' ', substr($docLine, 1), 2);
          }
          else
          {
            $type   = substr($docLine, 1);
            $detail = true;
          }
          $this->_object->setDocBlockItem($type, $detail);
        }
        else
        {
          $comment[] = trim(substr($docLine, 9));
        }
      }
      else if(!empty($docLine))
      {
        $comment[] = trim($docLine);
      }
    }

    if(!empty($comment))
    {
      $this->_object->setDocBlockComment(implode("\n", $comment));
    }

    return true;
  }
}
