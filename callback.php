<?php
include_once('config/symbini.php');
include_once($SERVER_ROOT.'/classes/OpenIdProfileManager.php');
require __DIR__ . '/vendor/autoload.php';
use Jumbojett\OpenIDConnectClient;

$profManager = new OpenIdProfileManager();

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
    var_dump($oidc); 
    // print_r($oidc->requestUserInfo('given_name'));
    $sub = $oidc->requestUserInfo('sub');
    // @TODO grab sub
    $profManager->authenticate($sub, $_SESSION['OID_ProviderURL']);
    // @TODO query db for user with said sub
    // @TODO sign in this user 
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
