<?php
/**
 * @author gareth.evans
 */

namespace Cubex\Email;

use Cubex\Facade\Email;

class Cli
{
  public function __construct()
  {
    if("" == ini_get("date.timezone"))
    {
      ini_set("date.timezone", "UTC");
    }

    $mailer = Email::getAccessor();

    $mailer->addRecipient("oke.ugwu@justdevelop.it");
    $mailer->setBody("my body");
    $mailer->setFrom("gareth.evans@justdevelop.it");
    $mailer->attach("C:\\Users\\gareth.evans\\Desktop\\47d427238a8c8c6ebc3930e75abd1993.jpeg");
    echo '<pre>';var_dump($mailer->send());echo '</pre>';
  }
}
