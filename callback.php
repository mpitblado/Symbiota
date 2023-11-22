<?php
include_once('config/symbini.php');
require __DIR__ . '/vendor/autoload.php';
use Jumbojett\OpenIDConnectClient;

$oidc = new OpenIDConnectClient($_SESSION['OID_ProviderURL'],
  $_SESSION['OID_clientID'],
  $_SESSION['OID_clientSecret'],
  $_SESSION['oidIssuer']);

// Needed for local Dev Env Only
//$oidc->setVerifyPeer(false);
$oidc->setHttpUpgradeInsecureRequests(false);


if (array_key_exists('code', $_REQUEST) && $_REQUEST['code']) {
  if($oidc->authenticate()){
    echo '<html><pre>';
    print_r($oidc->requestUserInfo('given_name'));
    //echo $user_email;
    //echo "Welcome in email: $user_email";
    //print_r($oidc);
    echo '</pre></html>';
  }
}

else {
  $arr = get_defined_vars();
  echo '<html><pre>';
  print_r($arr);
  echo '</pre></html>';
}
