<?php
/**
 * Requires https://packagist.org/packages/onigoetz/phpseclib
 * require: "onigoetz/phpseclib": "dev-master"
 *
 * @author  gareth.evans
 */
namespace Cubex\Encryption\Service;

use Cubex\Encryption\IEncryptionService;
use Cubex\ServiceManager\ServiceConfig;

class PhpseclibCryptRc4 implements IEncryptionService
{
  /**
   * @var \Crypt_RC4 $_encrypter
   */
  protected $_encryption;

  public function __construct()
  {
    $this->_encryption = new \Crypt_RC4();
  }

  public function configure(ServiceConfig $config)
  {
    $this->_encryption->setKey(
      $config->getStr("secret_key", "^*8dx50+mpvF)zw61Z27@N!yfTA\$JtVg")
    );
  }

  public function encrypt($value, array $options = [])
  {
    return $this->_encryption->encrypt($value);
  }

  public function decrypt($value, array $options = [])
  {
    return $this->_encryption->decrypt($value);
  }
}
