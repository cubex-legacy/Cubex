<?php
/**
 * Requires https://packagist.org/packages/illuminate/encryption
 * require: "illuminate/encryption": "4.0.*@dev"
 *
 * @author  gareth.evans
 */
namespace Cubex\Encryption\Service;

use Cubex\Encryption\IEncryptionService;
use Cubex\ServiceManager\ServiceConfig;

class IlluminateEncryption implements IEncryptionService
{
  private $_key;

  /**
   * @var \Illuminate\Encryption\Encrypter $_encrypter
   */
  protected $_encryption;

  public function configure(ServiceConfig $config)
  {
    $this->_key = $config->getStr(
      "secret_key", "^*8dx50+mpvF)zw61Z27@N!yfTA\$JtVg"
    );
  }

  private function _getEncryption()
  {
    if($this->_encryption === null)
    {
      $this->_encryption = new \Illuminate\Encryption\Encrypter($this->_key);
    }

    return $this->_encryption;
  }

  public function encrypt($value, array $options = [])
  {
    return $this->_getEncryption()->encrypt($value);
  }

  public function decrypt($value, array $options = [])
  {
    return $this->_getEncryption()->decrypt($value);
  }
}
