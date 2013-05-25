<?php
echo "Creating Cubex Phar";
ini_set('phar.readonly', 'Off'); //This should be set within configuration
$baseDir = dirname(__DIR__);
if(Phar::canWrite())
{
  $phar = new Phar($baseDir . '/Cubex.phar');
  $phar->buildFromDirectory($baseDir . '/src');
  if($phar->canCompress(Phar::GZ))
  {
    $phar->compress(Phar::GZ);
  }
}
else
{
  echo "Please make sure phar.readonly is set to Off\n";
}
