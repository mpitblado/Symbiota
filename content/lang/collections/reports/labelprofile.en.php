<?php
/*
------------------
Language: English
------------------
*/

if($LANG_TAG != 'en' && file_exists($SERVER_ROOT . '/content/lang/collections/reports/labelmanager.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT . '/content/lang/collections/reports/labelmanager.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT . '/content/lang/collections/reports/labelmanager.en.php');

$LANG['ERROR_SELECT_TARGET'] = 'ERROR: you must select a clone target!';
$LANG['NOW_EDIT_JSON'] = 'You should now be able to edit the JSON label definition. 
						Feel free to modify, but note that editing the raw JSON requires knowledge of the JSON format. 
						A simple error may cause label generation to completely fail.';
$LANG['SEL_TARGET'] = 'Select a clone target!';
$LANG['LABEL_MANAGER'] = 'Label Manager';
$LANG['LABEL_PROFILE_EDIT'] = 'Label Profile Editor';
$LANG['IN_DEV'] = 'In development!';
$LANG['PORTAL_PROFILES'] = 'Portal Profiles';
$LANG['USER_PROFILES'] = 'User Profiles';
$LANG['FORMATS'] = 'formats';
$LANG['CREATE_NEW_PROFILE'] = 'Create a new label profile';
$LANG['HEADER'] = 'Header';
$LANG['FOOTER'] = 'Footer';
$LANG['TYPE'] = 'Type';
$LANG['PAGE_SIZE'] = 'Page size';
$LANG['TITLE'] = 'Title';
$LANG['EDIT_LABEL_PROFILE'] = 'Edit label profile';
$LANG['LABEL_HEADER'] = 'Label Header';
$LANG['PREFIX'] = 'Prefix';
$LANG['SUFFIX'] = 'Suffix';
$LANG['CLASS_NAMES'] = 'Class names';
$LANG['CLASS_NAMES'] = 'Style';
$LANG['FOOTER_TEXT'] = 'Footer text';
$LANG['CUSTOM_STYLES'] = 'Custom Styles';
$LANG['DEFAULT_CSS'] = 'Default CSS';
$LANG['CUSTOM_CSS'] = 'Custom CSS';
$LANG['CUSTOM_JS'] = 'Custom JS';
$LANG['OPTIONS'] = 'Options';
$LANG['PAGE_SIZE'] = 'Page size';
$LANG['A4'] = 'A4';
$LANG['LEGAL'] = 'Legal'; //in reference to page size
$LANG['LEDGER'] = 'Ledger/Tabloid'; //in reference to page size
$LANG['DISPLAY_SP_AUTH_FOR_INFRA'] = 'Display species author for infraspecific taxa';
$LANG['DISPLAY_BARCODE'] = 'Display barcode';
$LANG['JSON'] = 'JSON';
$LANG['EDIT_JSON'] = 'Edit JSON label definition';
$LANG['EDIT_JSON_DEFINITION'] = 'Edit JSON label definition (Visual Interface)';
$LANG['SAVE_LABEL_PROFILE'] = 'Save Label Profile';
$LANG['CREATE_LABEL_PROFILE'] = 'Create New Label Profile';
$LANG['SURE_DELETE_PROFILE'] = 'Are you sure you want to delete this profile?';
$LANG['DEL_PROFILE'] = 'Delete Profile';
$LANG['SELECT_TARGET'] = 'Select Target';
$LANG['PORTAL_GLOBAL_PROFILE'] = 'Portal Global Profile';
$LANG['COLL_PROFILE'] = 'Collection Profile';
$LANG['USER_PROFILE'] = 'User Profile';
$LANG['NO_PROFILE_DEFINED'] = 'No label profile yet defined.';
$LANG['CLICK_GREEN_PLUS'] = 'Click green plus sign to right to create a new profile';
$LANG['NOT_AUTH_LABEL_PROF'] = 'You are not authorized to manage any label profiles. Contact portal administrator for more details.';

?>