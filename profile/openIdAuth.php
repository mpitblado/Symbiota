<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT . '/vendor/autoload.php');

use Jumbojett\OpenIDConnectClient;

$_SESSION['OID_ProviderURL'] = 'https://login.microsoftonline.com/f41b58ac-de75-4b41-986c-17eaa32822e9/v2.0/';
$_SESSION['OID_clientID'] = 'f4159842-0bca-4297-a860-ab3c9a997056';
$_SESSION['OID_clientSecret'] = 'WLr8Q~nlBr-v1XVEUgL3cWQG4BWLpbMFW.Omebs7';

$oidc = new OpenIDConnectClient($_SESSION['OID_ProviderURL'],
                                $_SESSION['OID_clientID'],
                                $_SESSION['OID_clientSecret']);

$oidc->addScope(array('openid'));
$oidc->addScope(array('email'));
$oidc->setResponseTypes(array('code'));
//$oidc->setResponseTypes(array('id_token'));
$oidc->setRedirectUrl('http://localhost/Symbiota/callback.php');

// Needed for local Dev Env Only
//$oidc->setVerifyPeer(false);
$oidc->setHttpUpgradeInsecureRequests(false);

$_SESSION['oidIssuer'] = $oidc->getIssuer();
$oidc->authenticate(); // @TODO redirect to landing page if authenticat returns true

//$name = $oidc->requestUserInfo('given_name');


?>

<html>
<head>
    <title>Example OpenID Connect Client Use</title>
    <style>
        body {
            font-family: 'Lucida Grande', Verdana, Arial, sans-serif;
        }
    </style>
</head>
<body>

    <div>
        Hello <?php echo $name; ?>
    </div>

</body>
</html>

