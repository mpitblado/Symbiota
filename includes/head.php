<?php
/*
** Style sheets are determined by $CSS_BASE_PATH set within config/symbini.php
** Customization can be made by modifying css files, $CSS_BASE_PATH, adding new css files below
*/


include_once($SERVER_ROOT.'/classes/ProfileManager.php');
$pHandler = new ProfileManager();
$isAccessiblePreferred = $pHandler->getAccessibilityPreference($SYMB_UID);
// $_SESSION['active_stylesheet'] = null; // use this if you want to troubleshoot the behavior of just the persisted preference
$localSession = isset($_SESSION['active_stylesheet']) ? $_SESSION['active_stylesheet'] : null;
?>
<!-- Responsive viewport -->
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- Symbiota styles -->
<link href="<?= $CSS_BASE_PATH ?>/symbiota/reset.css?ver=<?= $CSS_VERSION ?>" type="text/css" rel="stylesheet">
<link href="<?= $CSS_BASE_PATH ?>/symbiota/header.css?ver=<?= $CSS_VERSION ?>" type="text/css" rel="stylesheet">
<link href="<?= $CSS_BASE_PATH ?>/symbiota/footer.css?ver=<?= $CSS_VERSION ?>" type="text/css" rel="stylesheet">
<link href="<?= $CSS_BASE_PATH ?>/symbiota/main.css?ver=<?= $CSS_VERSION ?>" type="text/css" rel="stylesheet">
<!-- customizations moved to last css loaded -->

<script src="<?= $CLIENT_ROOT ?>/js/symb/lang.js" type="text/javascript"></script>
<script src="<?= $CLIENT_ROOT ?>/js/symb/accessibilityUtils.js" type="text/javascript"></script>
<script src="<?= $CLIENT_ROOT ?>/assets/uswds/js/uswds-init.min.js" type="text/javascript"></script>
<link href="<?= $USWDS_ASSETS ?>/symbiota/styleguide.css" type="text/css" rel="stylesheet">
<link href="<?= $USWDS_ASSETS ?>/symbiota/nal.js" type="text/css" rel="stylesheet">
<link href="<?= $CSS_BASE_PATH ?>/symbiota/customizations.css?ver=<?= $CSS_VERSION ?>" type="text/css" rel="stylesheet">
<style>
    .accessibility-button {
        font-size: 1.1em;
    }
    .welcome-text {
        margin-bottom: 0.75rem;
    }
</style>
<?php 
    if($isAccessiblePreferred){
        if(isset($localSession) && strpos($localSession, "condensed.css")){
            ?>
            <link href="<?php echo htmlspecialchars($CSS_BASE_PATH, HTML_SPECIAL_CHARS_FLAGS); ?>/symbiota/accessibility-compliant.css?ver=6.css" type="text/css" rel="stylesheet" data-accessibility-link="accessibility-css-link" disabled >
            <link href="<?php echo htmlspecialchars($CSS_BASE_PATH, HTML_SPECIAL_CHARS_FLAGS); ?>/symbiota/condensed.css?ver=6.css" type="text/css" rel="stylesheet" data-accessibility-link="accessibility-css-link" >
            <?php
        }else{
            ?>
            <link href="<?php echo htmlspecialchars($CSS_BASE_PATH, HTML_SPECIAL_CHARS_FLAGS); ?>/symbiota/accessibility-compliant.css?ver=6.css" type="text/css" rel="stylesheet" data-accessibility-link="accessibility-css-link" >
            <link href="<?php echo htmlspecialchars($CSS_BASE_PATH, HTML_SPECIAL_CHARS_FLAGS); ?>/symbiota/condensed.css?ver=6.css" type="text/css" rel="stylesheet" data-accessibility-link="accessibility-css-link" disabled >
            <?php
        }
    } else{
        if(isset($localSession) && strpos($localSession, "accessibility-compliant.css")){
            ?>
            <link href="<?php echo htmlspecialchars($CSS_BASE_PATH, HTML_SPECIAL_CHARS_FLAGS); ?>/symbiota/accessibility-compliant.css?ver=6.css" type="text/css" rel="stylesheet" data-accessibility-link="accessibility-css-link" >
            <link href="<?php echo htmlspecialchars($CSS_BASE_PATH, HTML_SPECIAL_CHARS_FLAGS); ?>/symbiota/condensed.css?ver=6.css" type="text/css" rel="stylesheet" data-accessibility-link="accessibility-css-link" disabled >
            <?php
        } else{
            ?>
            <link href="<?php echo htmlspecialchars($CSS_BASE_PATH, HTML_SPECIAL_CHARS_FLAGS); ?>/symbiota/accessibility-compliant.css?ver=6.css" type="text/css" rel="stylesheet" data-accessibility-link="accessibility-css-link" disabled >
            <link href="<?php echo htmlspecialchars($CSS_BASE_PATH, HTML_SPECIAL_CHARS_FLAGS); ?>/symbiota/condensed.css?ver=6.css" type="text/css" rel="stylesheet" data-accessibility-link="accessibility-css-link" >
            <?php
        }
    }
?>
