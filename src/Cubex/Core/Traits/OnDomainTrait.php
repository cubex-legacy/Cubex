<?php
/**
 * @author  brooke.bryan
 */

namespace Cubex\Core\Traits;

use Cubex\Core\Http\Request;

trait OnDomainTrait
{
  abstract public function setTitle($title);

  abstract public function addMeta($name, $content);

  abstract public function addDynamicMeta($data);

  /**
   * @return Request
   */
  abstract public function request();

  public function setTitleOnDomain($title = '', $domain = null, $tld = null)
  {
    if($this->request()->matchDomain($domain, $tld))
    {
      $this->setTitle($title);
    }
    return $this;
  }

  public function addMetaOnDomain($name, $content, $domain = null, $tld = null)
  {
    if($this->request()->matchDomain($domain, $tld))
    {
      $this->addMeta($name, $content);
    }
    return $this;
  }

  public function addDynamicMetaOnDomain($data, $domain = null, $tld = null)
  {
    if($this->request()->matchDomain($domain, $tld))
    {
      $this->addDynamicMeta($data);
    }
    return $this;
  }
}
