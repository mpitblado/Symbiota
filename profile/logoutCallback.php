<?php
include_once('../config/symbini.php'); // @TODO think about how this (new?) session affects things
use Jumbojett\OpenIDConnectClient;

$AUTH_PROVIDER = $AUTH_PROVIDER ?? 'oid';
$oidc = new OpenIDConnectClient($providerUrls[$AUTH_PROVIDER], $clientIds[$AUTH_PROVIDER], $clientSecrets[$AUTH_PROVIDER], $providerUrls[$AUTH_PROVIDER]); // assumes that the issuer is identical to the providerUrl, as seems to be the case for microsoft

// @TODO instantiate $sessionManager

if($oidc->verifyLogoutToken()){
    $session_id = $oidc->getSidFromBackChannel();
    if(isset($session_id)){
        // lookup active sessions related to this session_id
        $sessionManager->purgeUserSession($session_id);
    }
}

//request might include
//provider, sub_uuid



?>