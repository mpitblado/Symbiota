<?php
class SessionManager {
    // protected function resetThirdParty(){
	// 	// find session id
	// 	$domainName = filter_var($_SERVER['SERVER_NAME'], FILTER_SANITIZE_URL);
	// 	if($domainName == 'localhost') $domainName = false;
	// 	setcookie('SymbiotaCrumb', '', time() - 3600, ($GLOBALS['CLIENT_ROOT']?$GLOBALS['CLIENT_ROOT']:'/'), $domainName, false, true);
	// 	setcookie('SymbiotaCrumb', '', time() - 3600, ($GLOBALS['CLIENT_ROOT']?$GLOBALS['CLIENT_ROOT']:'/'));
	// 	unset($_SESSION['userrights']);
	// 	unset($_SESSION['userparams']);
	// 	// session_register_shutdown()
	// }

    public function purgeUserSession($session_id){
        $targetSessionFilename = sys_get_temp_dir() . 'sess_' . $session_id;

        if(file_exists($targetSessionFilename)){

        }
        // if(array_key_exists($session_id, $_SESSION)){
        //     $sessionToDestroy = $_SESSION($session_id);

        // }
        // calculate file name($session_id)
        //if (filename) delete()
    }

    // public function handleThirdPartyLogoutRequest(){

    //     // set up an endpoint that third party is gonna request (e.g., log out user with sub_uuid)
    //     //...
    //     // determine which session to log out, then:
    //     // purgeUserSession(session_id)
    // }

}
?>