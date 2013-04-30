<?php
/**
 * @author  gareth.evans
 */
namespace Cubex\Encryption;

use Cubex\ServiceManager\IService;

interface EncryptionService extends IService
{
  public function encrypt($value, array $options = []);
  public function decrypt($value, array $options = []);
}
