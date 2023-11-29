<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT . '/vendor/autoload.php');
include_once($SERVER_ROOT . '/config/auth_config.php');

use Jumbojett\OpenIDConnectClient;

// $_SESSION['refUrl'] = array_key_exists('refUrl', $_REQUEST) ? $_REQUEST['refUrl'] : ''; // $_SERVER['SCRIPT_NAME'];

$oidc = new OpenIDConnectClient($providerUrls['oid'],
                                $clientIds['oid'],
                                $clientSecrets['oid']);

$oidc->addScope(array('openid'));
$oidc->addScope(array('email'));
$oidc->setResponseTypes(array('code'));
//$oidc->setResponseTypes(array('id_token'));
$oidc->setRedirectUrl('http://localhost/Symbiota/callback.php');

// Needed for local Dev Env Only
//$oidc->setVerifyPeer(false);
$oidc->setHttpUpgradeInsecureRequests(false);

// $_SESSION['oidIssuer'] = $oidc->getIssuer(); // moot for microsoft, but potentially useful for other auth providers?
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

