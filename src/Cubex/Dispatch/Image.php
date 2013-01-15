<?php
/**
 * @author: gareth.evans
 */
namespace Cubex\Dispatch;

class Image extends Prop
{
  public function getUri(Event $event)
  {
    $file = $event->getFile();

    if($this->_fabricate()->isExternalUri($file))
    {
      return $file;
    }

    return $this->_fabricate()->dispatchUri($file);
  }
}
