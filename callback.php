<?php
include_once('config/symbini.php');
include_once($SERVER_ROOT.'/classes/OpenIdProfileManager.php');
include_once($SERVER_ROOT . '/config/auth_config.php');
require __DIR__ . '/vendor/autoload.php';
use Jumbojett\OpenIDConnectClient;

$profManager = new OpenIdProfileManager();

$oidc = new OpenIDConnectClient($providerUrls['oid'], $clientIds['oid'], $clientSecrets['oid'], $providerUrls['oid']); // assumes that the issuer is identical to the providerUrl, as seems to be the case for microsoft

// Needed for local Dev Env Only TODO: Move this to paramaterized login in auth_config
//$oidc->setVerifyPeer(false);
$oidc->setHttpUpgradeInsecureRequests(false);


if (array_key_exists('code', $_REQUEST) && $_REQUEST['code']) {
  if($oidc->authenticate()){
    $sub = $oidc->requestUserInfo('sub');
    if($profManager->authenticate($sub, $providerUrls['oid'])){
      if($_SESSION['refurl']){
        header("Location:" . $_SESSION['refurl']);
        unset($_SESSION['refurl']);
      }
    }
    else {
      if ($email = $oidc->requestUserInfo('email')){
        // Authprovider returned a subscriber, however user was not authenticated to local user account
        //echo "Looking for email: $email";
        if($profManager->linkLocalUserOidSub($email, $sub, $oidc->getProviderURL())){
          if($profManager->authenticate($sub, $providerUrls['oid'])){
            if($_SESSION['refurl']){
              header("Location:" . $_SESSION['refurl']);
              unset($_SESSION['refurl']);
            }
          }
          else{
            $_SESSION['last_message'] = "Unkown Error - Could not authenticate - try again later or alert a system admin <ERR/>";
            header('Location:' . $CLIENT_ROOT . '/profile/index.php');
            //@TODO Consider logging this error to PHP logfiles
          }
        }
        else{
          $_SESSION['last_message'] = "Error - Could not authenticate with Authentication provider <ERR/>";
          header('Location:' . $CLIENT_ROOT . '/profile/index.php');
        }
      }
      else{
        //authentication failed
        $arr = get_defined_vars();
        echo '<html><pre>';
        print_r($arr);
        echo '</pre></html>';
      }
    }
  }
    // @TODO need to handle the case of unsuccessful authentication
}