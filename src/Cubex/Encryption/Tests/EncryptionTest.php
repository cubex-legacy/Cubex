<?php
/**
 * @author  gareth.evans
 */
namespace Cubex\Encryption\Tests;

use Cubex\Encryption\Service\TestEncryption;
use Cubex\Tests\TestCase;

class EncryptionTest extends TestCase
{
  private $_value;
  private $_valueReversed;

  /**
   * @var \Cubex\Encryption\IEncryptionService $_encryption
   */
  private $_encryption;

  public function setUp()
  {
    $this->_value         = base_convert(rand(10000, 9999999), 20, 36);
    $this->_valueReversed = strrev($this->_value);

    $this->_encryption = new TestEncryption();
  }

  public function testEncrypt()
  {
    $this->assertEquals(
      $this->_valueReversed, $this->_encryption->encrypt($this->_value)
    );
  }

  public function testDecryption()
  {
    $this->assertEquals(
      $this->_value, $this->_encryption->decrypt($this->_valueReversed)
    );
  }
}
