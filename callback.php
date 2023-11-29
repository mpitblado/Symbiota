<?php
include_once('config/symbini.php');
include_once($SERVER_ROOT.'/classes/OpenIdProfileManager.php');
include_once($SERVER_ROOT . '/config/auth_config.php');
require __DIR__ . '/vendor/autoload.php';
use Jumbojett\OpenIDConnectClient;

$profManager = new OpenIdProfileManager();

$oidc = new OpenIDConnectClient($providerUrls['oid'], $clientIds['oid'], $clientSecrets['oid'], $providerUrls['oid']); // assumes that the issuer is identical to the providerUrl, as seems to be the case for microsoft
  // $_SESSION['oidIssuer']); deleteMe

// Needed for local Dev Env Only
//$oidc->setVerifyPeer(false);
$oidc->setHttpUpgradeInsecureRequests(false);


if (array_key_exists('code', $_REQUEST) && $_REQUEST['code']) {
  if($oidc->authenticate()){
    echo '<html><pre>';
    // print_r($oidc->requestUserInfo('given_name'));
    $sub = $oidc->requestUserInfo('sub');
    // @TODO grab sub
    $profManager->authenticate($sub, $providerUrls['oid']);
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
