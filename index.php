<?php
include_once('config/symbini.php');
if($LANG_TAG == 'en' || !file_exists($SERVER_ROOT.'/content/lang/index.'.$LANG_TAG.'.php')) include_once($SERVER_ROOT.'/content/lang/index.en.php');
else include_once($SERVER_ROOT.'/content/lang/index.'.$LANG_TAG.'.php');
header('Content-Type: text/html; charset=' . $CHARSET);
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<title><?php echo $DEFAULT_TITLE; ?> Home</title>
	<?php
	include_once($SERVER_ROOT . '/includes/head.php');
	include_once($SERVER_ROOT . '/includes/googleanalytics.php');
	?>
</head>
<body>
	<?php
	include($SERVER_ROOT . '/includes/header.php');
	?>
	<div class="navpath"></div>
	<div id="innertext">
		<?php
		if($LANG_TAG == 'es'){
			?>
			<div>
				<h1 class="headline">Bienvenidos</h1>
				<p>Este portal de datos se ha establecido para promover la colaboración... Reemplazar con texto introductorio en inglés</p>
			</div>
			<?php
		}
		elseif($LANG_TAG == 'fr'){
			?>
			<div>
				<h1 class="headline">Bienvenue</h1>
				<p>Ce portail de données a été créé pour promouvoir la collaboration... Remplacer par le texte d'introduction en anglais</p>
			</div>
			<?php
		}
		else{
			//Default Language
			?>
			<div>
				<ul class="usa-card-group">
					<li class="usa-card tablet:grid-col-4">
						<div class="usa-card__container">
						<div class="usa-card__header">
							<h2 class="usa-card__heading">U.S. National Arboretum Herbarium (NA)</h2>
						</div>
						<div class="usa-card__media">
							<div class="usa-card__img">
							<img class="card-image"
								src="<?php echo $CLIENT_ROOT ?>/assets/uswds/img/NA_tile.png"
								alt="An image of a flower"
							/>
							</div>
						</div>
						<div class="usa-card__body">
							<p>
							700,000 pressed, preserved plant specimens representing USDA research and botanical exploration.
							</p>
						</div>
						<div class="usa-card__footer">
							<a href="#" class="usa-button card-button">Access the Specimens</a>
						</div>
						</div>
					</li>
					<li class="usa-card tablet:grid-col-4">
						<div class="usa-card__container">
						<div class="usa-card__header">
							<h2 class="usa-card__heading">U.S. National Seed Herbarium (BARC)</h2>
						</div>
						<div class="usa-card__media">
							<div class="usa-card__img">
							<img class="card-image"
								src="<?php echo $CLIENT_ROOT ?>/assets/uswds/img/BARC_tile.png"
								alt="An image of a seed"
							/>
							</div>
						</div>
						<div class="usa-card__body">
							<p>
							Over 150,000 preserved seed and fruit samples, primarily of non-native plant species.
							</p>
						</div>
						<div class="usa-card__footer">
							<a href="#" class="usa-button card-button">Access the Specimens</a>
						</div>
						</div>
					</li>
					<li class="usa-card tablet:grid-col-4">
						<div class="usa-card__container">
						<div class="usa-card__header">
							<h2 class="usa-card__heading">U.S. National Fungus Collections (BPI)</h2>
						</div>
						<div class="usa-card__media">
							<div class="usa-card__img">
							<img class="card-image"
								src="data:image/jpeg;base64,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"
								alt="An image of a mushroom"
							/>
							</div>
						</div>
						<div class="usa-card__body">
							<p>
							The Western Hemisphere’s largest fungal herbarium, including the John A. Stevenson Mycological Library.
							</p>
						</div>
						<div class="usa-card__footer">
							<a href="https://www.mycoportal.org/portal/" class="usa-button card-button">Access the Specimens</a>
						</div>
						</div>
					</li>
				</ul>
			</div>
			<?php
		}
		?>
	</div>
	<?php
	include($SERVER_ROOT . '/includes/footer.php');
	?>
</body>
</html>
