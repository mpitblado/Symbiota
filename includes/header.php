<?php
if($LANG_TAG == 'en' || !file_exists($SERVER_ROOT.'/content/lang/header.'.$LANG_TAG.'.php')) include_once($SERVER_ROOT.'/content/lang/header.en.php');
else include_once($SERVER_ROOT.'/content/lang/header.'.$LANG_TAG.'.php');
include_once($SERVER_ROOT.'/classes/ProfileManager.php');
$pHandler = new ProfileManager();
$isAccessiblePreferred = $pHandler->getAccessibilityPreference($SYMB_UID);
?>
<style>
	.custom-header{
		color: black;
	}
	.header-bg{
		background-image: url('<?php echo $CLIENT_ROOT; ?>/images/layout/logo_symbiota.png');
	}
</style>
<header class="usa-header usa-header--basic custom-header" role="banner" aria-label="Page Header" style="background-color: white;">
	<div class="usa-banner" style="width: 100%;">
		<div class="usa-accordion">
			<header class="usa-banner-header" aria-label="Official United States Government Website Disclaimer"></header>
		</div>	
		<div class="usa-grid usa-banner-inner">
			<img src="<?php echo $SERVER_ROOT ?>/assets/uswds/img/favicons/flag-favicon-57.png" alt="U.S. flag">
			<p>An official website of the United States government</p>
			<button class="usa-accordion-button usa-banner-button" aria-expanded="false" aria-controls="gov-banner">
				<span class="usa-banner-button-text">Here’s how you know</span>
			</button>
		</div>
	</div>

	<div class="usa-overlay"></div>
  <div class="usa-nav-container">
    <div class="usa-navbar">
      <div class="usa-logo custom-header">
        <em class="usa-logo__text">
			<a href="https://symbiota.org" title="Symbiota" class="header-bg">
				USDA Symbiota
			</a>
		</em>
      </div>
      <button type="button" class="usa-menu-btn">Menu</button>
    </div>
    <nav aria-label="Primary navigation" class="usa-nav">
      <button type="button" class="usa-nav__close">
        <img src="/assets/img/usa-icons/close.svg" role="img" alt="Close" />
      </button>
      <ul class="usa-nav__primary usa-accordion">
        <li class="usa-nav__primary-item">
			<a href="<?php echo $CLIENT_ROOT; ?>/index.php">
				<?php echo (isset($LANG['H_HOME'])?$LANG['H_HOME']:'Home'); ?>
			</a>
        </li>
		<li class="usa-nav__primary-item">
			<a href="<?php echo $CLIENT_ROOT; ?>/collections/index.php">
				<?php echo (isset($LANG['H_COLLECTIONS'])?$LANG['H_COLLECTIONS']:'Collections'); ?>
			</a>
        </li>
		<li class="usa-nav__primary-item">
				<a href="<?php echo $CLIENT_ROOT; ?>/collections/map/index.php" target="_blank" rel="noopener noreferrer">
					<?php echo (isset($LANG['H_MAP_SEARCH'])?$LANG['H_MAP_SEARCH']:'Map Search'); ?>
				</a>
        </li>
		<li class="usa-nav__primary-item">
			<a href="<?php echo $CLIENT_ROOT; ?>/checklists/index.php">
				<?php echo (isset($LANG['H_INVENTORIES'])?$LANG['H_INVENTORIES']:'Checklists'); ?>
			</a>
        </li>
		<li class="usa-nav__primary-item">
			<a href="<?php echo $CLIENT_ROOT; ?>/imagelib/search.php">
				<?php echo (isset($LANG['H_IMAGES'])?$LANG['H_IMAGES']:'Images'); ?>
			</a>
        </li>
		<li class="usa-nav__primary-item">
			<a href="<?php echo $CLIENT_ROOT; ?>/includes/usagepolicy.php">
				<?php echo (isset($LANG['H_DATA_USAGE'])?$LANG['H_DATA_USAGE']:'Data Use'); ?>
			</a>
        </li>
		<li class="usa-nav__primary-item">
			<a href="https://symbiota.org/docs" target="_blank" rel="noopener noreferrer">
				<?php echo (isset($LANG['H_HELP'])?$LANG['H_HELP']:'Help'); ?>
			</a>
        </li>
        <li class="usa-nav__primary-item">
          	<a href='<?php echo $CLIENT_ROOT; ?>/sitemap.php'>
				<?php echo (isset($LANG['H_SITEMAP'])?$LANG['H_SITEMAP']:'Sitemap'); ?>
			</a>
        </li>
      </ul>
      <section aria-label="Search component">
        <form class="usa-search usa-search--small" role="search">
          <label class="usa-sr-only" for="search-field">Search</label>
          <input
            class="usa-input"
            id="search-field"
            type="search"
            name="search"
          />
          <button class="usa-button" type="submit">
            <img
              src="/assets/img/usa-icons-bg/search--white.svg"
              class="usa-search__submit-icon"
              alt="Search"
            />
          </button>
        </form>
      </section>
    </nav>
  </div>
</header>
<div class="header-wrapper">
	<header class="usa-header usa-header-extended custom-header" role="banner" aria-label="Page Header">
		<div class="usa-banner">
			<div class="usa-accordion">
				<header class="usa-banner-header" aria-label="Official United States Government Website Disclaimer">
			</div>
			<nav class="top-login">
				<?php
				if ($USER_DISPLAY_NAME) {
					?>
					<span>
						<?php echo (isset($LANG['H_WELCOME'])?$LANG['H_WELCOME']:'Welcome').' '.$USER_DISPLAY_NAME; ?>!
					</span>
					<span class="button button-tertiary">
						<a class="accessibility-button" onclick="toggleAccessibilityStyles('<?php echo $CLIENT_ROOT . '/includes' . '/' ?>', '<?php echo $CSS_BASE_PATH ?>', '<?php echo $LANG['TOGGLE_508_OFF'] ?>', '<?php echo $LANG['TOGGLE_508_ON'] ?>')" id="accessibility-button" data-accessibility="accessibility-button" ><?php echo (isset($LANG['TOGGLE_508_ON'])?$LANG['TOGGLE_508_ON']:'Accessibility Mode'); ?></a>
					</span>
					<span class="button button-tertiary">
						<a href="<?php echo htmlspecialchars($CLIENT_ROOT, HTML_SPECIAL_CHARS_FLAGS); ?>/profile/viewprofile.php"><?php echo htmlspecialchars((isset($LANG['H_MY_PROFILE'])?$LANG['H_MY_PROFILE']:'My Profile'), HTML_SPECIAL_CHARS_FLAGS)?></a>
					</span>
					<span class="button button-secondary">
						<a href="<?php echo htmlspecialchars($CLIENT_ROOT, HTML_SPECIAL_CHARS_FLAGS); ?>/profile/index.php?submit=logout"><?php echo htmlspecialchars((isset($LANG['H_LOGOUT'])?$LANG['H_LOGOUT']:'Sign Out'), HTML_SPECIAL_CHARS_FLAGS)?></a>
					</span>
					<?php
				} else {
					?>
					<span class="button button-tertiary">
						<a class="accessibility-button" onclick="toggleAccessibilityStyles('<?php echo $CLIENT_ROOT . '/includes' . '/' ?>', '<?php echo $CSS_BASE_PATH ?>', '<?php echo $LANG['TOGGLE_508_OFF'] ?>', '<?php echo $LANG['TOGGLE_508_ON'] ?>')" id="accessibility-button" data-accessibility="accessibility-button" ><?php echo (isset($LANG['TOGGLE_508_ON'])?$LANG['TOGGLE_508_ON']:'Accessibility Mode'); ?></a>
					</span>
					<span class="button button-tertiary">
						<a onclick="window.location.href='#'">
							Contact Us
						</a>
					</span>
					<span class="button button-secondary">
						<a href="<?php echo htmlspecialchars($CLIENT_ROOT, HTML_SPECIAL_CHARS_FLAGS) . "/profile/index.php?refurl=" . htmlspecialchars($_SERVER['SCRIPT_NAME'], HTML_SPECIAL_CHARS_FLAGS) . "?" . htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES); ?>">
							<?php echo (isset($LANG['H_LOGIN'])?$LANG['H_LOGIN']:'Login')?>
						</a>
					</span>
					<?php
				}
				?>
			</nav>
			<div class="top-brand">
				<a href="https://symbiota.org">
					<div class="image-container">
						<img src="<?php echo $CLIENT_ROOT; ?>/images/layout/logo_symbiota.png" alt="Symbiota logo">
					</div>
				</a>
				<div class="brand-name">
					<h1>Symbiota Brand New Portal</h1>
					<h2>Redesigned by the Symbiota Support Hub</h2>
				</div>
			</div>
		</div>
		<div>
			<!-- Hamburger icon -->
			<input class="side-menu" type="checkbox" id="side-menu" />
			<label class="hamb" for="side-menu"><span class="hamb-line"></span></label>
			<!-- Menu -->
			<nav class="top-menu">
				<ul class="menu">
					<li>
						<a href="<?php echo $CLIENT_ROOT; ?>/index.php">
							<?php echo (isset($LANG['H_HOME'])?$LANG['H_HOME']:'Home'); ?>
						</a>
					</li>
					<li>
						<a href="<?php echo $CLIENT_ROOT; ?>/collections/index.php">
							<?php echo (isset($LANG['H_COLLECTIONS'])?$LANG['H_COLLECTIONS']:'Collections'); ?>
						</a>
					</li>
					<li>
						<a href="<?php echo $CLIENT_ROOT; ?>/collections/map/index.php" target="_blank" rel="noopener noreferrer">
							<?php echo (isset($LANG['H_MAP_SEARCH'])?$LANG['H_MAP_SEARCH']:'Map Search'); ?>
						</a>
					</li>
					<li>
						<a href="<?php echo $CLIENT_ROOT; ?>/checklists/index.php">
							<?php echo (isset($LANG['H_INVENTORIES'])?$LANG['H_INVENTORIES']:'Checklists'); ?>
						</a>
					</li>
					<li>
						<a href="<?php echo $CLIENT_ROOT; ?>/imagelib/search.php">
							<?php echo (isset($LANG['H_IMAGES'])?$LANG['H_IMAGES']:'Images'); ?>
						</a>
					</li>
					<li>
						<a href="<?php echo $CLIENT_ROOT; ?>/includes/usagepolicy.php">
							<?php echo (isset($LANG['H_DATA_USAGE'])?$LANG['H_DATA_USAGE']:'Data Use'); ?>
						</a>
					</li>
					<li>
						<a href="https://symbiota.org/docs" target="_blank" rel="noopener noreferrer">
							<?php echo (isset($LANG['H_HELP'])?$LANG['H_HELP']:'Help'); ?>
						</a>
					</li>
					<li>
						<a href='<?php echo $CLIENT_ROOT; ?>/sitemap.php'>
							<?php echo (isset($LANG['H_SITEMAP'])?$LANG['H_SITEMAP']:'Sitemap'); ?>
						</a>
					</li>
					<li>
						<select onchange="setLanguage(this)">
							<option value="en">English</option>
							<option value="es" <?php echo ($LANG_TAG=='es'?'SELECTED':''); ?>>Espa&ntilde;ol</option>
							<option value="fr" <?php echo ($LANG_TAG=='fr'?'SELECTED':''); ?>>Français</option>
						</select>
					</li>
				</ul>
			</nav>
		</div>
		<div id="end-nav"></div>
	</header>
</div>