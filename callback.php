<?php
include_once('config/symbini.php');
include_once($SERVER_ROOT.'/classes/OpenIdProfileManager.php');
include_once($SERVER_ROOT . '/config/auth_config.php');
require __DIR__ . '/vendor/autoload.php';
use Jumbojett\OpenIDConnectClient;

$profManager = new OpenIdProfileManager();

$oidc = new OpenIDConnectClient($providerUrls['oid'], $clientIds['oid'], $clientSecrets['oid'], $providerUrls['oid']); // assumes that the issuer is identical to the providerUrl, as seems to be the case for microsoft

// Needed for local Dev Env Only
//$oidc->setVerifyPeer(false);
$oidc->setHttpUpgradeInsecureRequests(false);


if (array_key_exists('code', $_REQUEST) && $_REQUEST['code']) {
  if($oidc->authenticate()){
    $sub = $oidc->requestUserInfo('sub');
    if($profManager->authenticate($sub, $providerUrls['oid'])){
      if($_SESSION['refurl']){
        header("Location:" . $_SESSION['refurl']);
      }
    }
    // @TODO need to handle the case of unsuccessful authentication
  }
}

else {
  $arr = get_defined_vars();
  echo '<html><pre>';
  print_r($arr);
  echo '</pre></html>';
}