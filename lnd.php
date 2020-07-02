<?php

// Path to autoload.php created by composer.
require dirname(__FILE__).'/../vendor/autoload.php';


error_reporting(2);

// LND node IP and Port (lnd option "rpcport").
$lndIp   = '';


// We need to set env variables to make ssl work:
putenv('GRPC_SSL_CIPHER_SUITES=ECDHE-RSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-AES128-SHA256');

// Get tls and macaroon paths.
$tlsPath = '';
$macaroonPath = '';
$local_user = posix_getpwuid(posix_getuid());

switch (PHP_OS) {
  case "Linux":
    $tlsPath = $local_user['dir'] . '/.lnd/tls.cert';
    $macaroonPath = $local_user['dir'] . '/.lnd/macaroon.admin';
}

if (! $sslCert = file_get_contents($tlsPath)) {
  $certError = <<<EOT
    tls.cert not found in "example" directory. Make sure to copy it from your 
    LND config directory.
    MacOS: ~/Library/Application Support/Lnd/tls.cert
    Linux: ~/.lnd/tls.cert
EOT;

  throw new Exception($certError);
}



try {
  $client = new Lnrpc\LightningClient($lndIp, [
    'credentials' => Grpc\ChannelCredentials::createSsl($sslCert),
    'update_metadata' => addMacaroon('kasdkf')
  ]);
  var_dump($client);
} catch (Exception $e) {
  throw new Exception($e->getMessage());
}


if (is_null($client)) {
  throw new Exception('Could not connect or authenticate to LND node.');
}

print(PHP_EOL);
