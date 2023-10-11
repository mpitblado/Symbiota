<?php
if($LANG_TAG == 'en' || !file_exists($SERVER_ROOT.'/content/lang/header.'.$LANG_TAG.'.php')) include_once($SERVER_ROOT.'/content/lang/header.en.php');
else include_once($SERVER_ROOT.'/content/lang/header.'.$LANG_TAG.'.php');
include_once($SERVER_ROOT.'/classes/ProfileManager.php');
$pHandler = new ProfileManager();
$isAccessiblePreferred = $pHandler->getAccessibilityPreference($SYMB_UID);
?>
<a class="usa-skipnav" href="#main-content">Skip to main content</a>
<header class="usa-header usa-header-basic" role="banner">
  <!-- Gov banner BEGIN -->
  <div class="usa-banner">
    <div class="usa-accordion">
      <header class="usa-banner-header">
        <div class="usa-grid usa-banner-inner">
          <img
            src="<?php echo $CLIENT_ROOT ?>/assets/uswds/img/us_flag_small.png"
            alt="U.S. flag"
          />
          <p>An official website of the United States government</p>
          <button
            class="usa-accordion__button usa-banner-button"
            aria-expanded="false"
            aria-controls="gov-banner"
            style="background-image: url('../css/uswds/img/usa-icons/angle-arrow-down-hover.svg');"
          >
            <span class="usa-banner-button-text">Here's how you know</span>
          </button>
        </div>
      </header>
      <div
        class="usa-banner-content usa-grid usa-accordion-content"
        id="gov-banner"
      >
        <div class="usa-banner-guidance-gov usa-width-one-half">
          <img
            class="usa-banner-icon usa-media_block-img"
            src="<?php echo $CLIENT_ROOT ?>/assets/uswds/img/icon-dot-gov.svg"
            alt="Dot gov"
          />
          <div class="usa-media_block-body">
            <p>
              <strong>Official websites use .gov</strong>
              <br />
              A <strong>.gov</strong> website belongs to an official government
              organization in the United States.
            </p>
          </div>
        </div>
        <div class="usa-banner-guidance-ssl usa-width-one-half">
          <img
            class="usa-banner-icon usa-media_block-img"
            src="<?php echo $CLIENT_ROOT ?>/assets/uswds/img/icon-https.svg"
            alt="SSL"
          />
          <div class="usa-media_block-body">
            <p>
              <strong>Secure .gov websites use HTTPS</strong>
              <br />
              A <strong>lock</strong> (
              <span class="icon-lock">
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="52"
                  height="64"
                  viewBox="0 0 52 64"
                  class="usa-banner__lock-image"
                  role="img"
                  aria-labelledby="banner-lock-title banner-lock-description"
                >
                  <title id="banner-lock-title">Lock</title>
                  <desc id="banner-lock-description">A locked padlock</desc>
                  <path
                    fill="#000000"
                    fill-rule="evenodd"
                    d="M26 0c10.493 0 19 8.507 19 19v9h3a4 4 0 0 1 4 4v28a4 4 0 0 1-4 4H4a4 4 0 0 1-4-4V32a4 4 0 0 1 4-4h3v-9C7 8.507 15.507 0 26 0zm0 8c-5.979 0-10.843 4.77-10.996 10.712L15 19v9h22v-9c0-6.075-4.925-11-11-11z"
                  >
                  </path>
                </svg>
              </span>

              ) or <strong>https://</strong> means you’ve safely connected to
              the .gov website. Share sensitive information only on official,
              secure websites.
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Gov banner END -->
  <!-- Logo  and Log-Text Begin -->
  <div class="usa-nav-container">
    <div class="usa-navbar">
      <button class="usa-menu-btn">Menu</button>
      <div class="usa-logo" id="logo">
        <span
          href="https://www.usda.gov"
          title="Home"
          rel="home"
          class="usda-logo"
        >
          <img
            src="<?php echo $CLIENT_ROOT ?>/assets/uswds/img/usda-symbol.svg"
            alt="USDA Logo"
            class="usda-logo-img"
          />
        </span>

        <em class="usa-logo-text">
          <a
            href="https://www.ars.usda.gov/"
            title="Agricultural Research Service Home"
            aria-label="The Agricultural Research Service Home Page"
            rel="home"
            id="anch_1"
            >Agricultural Research Service
          </a>

          <h6>
            <a
              href="https://www.usda.gov/"
              title="U.S. DEPARTMENT OF AGRICULTURE Home"
              aria-label="The U.S. Department of Agriculture Home Page"
              rel="home"
              id="anch_1"
              >U.S. DEPARTMENT OF AGRICULTURE
            </a>
          </h6>
        </em>
      </div>
    </div>

    <!-- Logo  and Log-Text End -->
    <nav role="navigation" class="usa-nav usa-color-primary-darkest">
      <button class="usa-nav-close">
        <img src="<?php echo $CLIENT_ROOT ?>/assets/uswds/img/close.svg" alt="close" />
      </button>

      <ul class="usa-nav-primary usa-accordion">
        <li>
          <button
            class="usa-accordion__button usa-nav-link"
            aria-expanded="false"
            aria-controls="side-nav-1"
            id="btnMenu-1"
          >
            <span id="menuName">Research</span>
          </button>
          <ul
            id="side-nav-1"
            class="usa-nav-submenu usa-color-white"
            aria-hidden="true"
          >
            <li><a href="/research/">Research Home</a></li>
            <li><a href="/research/programs/">National Programs</a></li>
            <li><a href="/research/projects/">Research Projects</a></li>
            <li>
              <a href="/research/publications/find-a-publication/"
                >Scientific Manuscripts</a
              >
            </li>
            <li>
              <a
                href="/office-of-international-research-engagement-and-cooperation/office-of-international-research-engagement-and-cooperation/"
                >International Engagement</a
              >
            </li>
            <li>
              <a href="/research/software/">Scientific Software/Models</a>
            </li>
            <li><a href="/research/datasets/">Databases and Datasets</a></li>
            <li>
              <a href="/office-of-scientific-quality-review-osqr/"
                >Office of Scientific Quality Review</a
              >
            </li>
          </ul>
        </li>
        <li>
          <button
            class="usa-accordion__button usa-nav-link"
            aria-expanded="false"
            aria-controls="side-nav-2"
            id="btnMenu-2"
          >
            <span id="menuName">Media</span>
          </button>
          <ul
            id="side-nav-2"
            class="usa-nav-submenu usa-color-white"
            aria-hidden="true"
          >
            <li><a href="/news-events/news-events/">News &amp; Features</a></li>
            <li><a href="/oc/ars-wired/">Multimedia</a></li>
            <li><a href="/oc/br/briefing-room/">Briefing Room</a></li>
            <li><a href="/oc/dof/archive/">Down on the Farm</a></li>
            <li><a href="/oc/press-room/">Press Room</a></li>
            <li>
              <a href="/oc/scienceinyourshoppingcart/siysc-factsheets"
                >Science in Your Shopping Cart</a
              >
            </li>
            <li>
              <a href="https://scientificdiscoveries.ars.usda.gov"
                >Scientific Discoveries</a
              >
            </li>
            <li><a href="https://tellus.ars.usda.gov/">Tellus</a></li>
            <li><a href="/oc/utm/archive">Under the Microscope</a></li>
          </ul>
        </li>
        <li>
          <button
            class="usa-accordion__button usa-nav-link"
            aria-expanded="false"
            aria-controls="side-nav-3"
            id="btnMenu-3"
          >
            <span id="menuName">About ARS</span>
          </button>
          <ul
            id="side-nav-3"
            class="usa-nav-submenu usa-color-white"
            aria-hidden="true"
          >
            <li><a href="/about-ars/">About ARS Home</a></li>
            <li>
              <a href="/people-locations/find-a-person/">Staff Directory</a>
            </li>
            <li>
              <a href="/people-locations/find-a-location/"
                >Labs and Research Centers (Map)</a
              >
            </li>
            <li>
              <a href="/docs/headquarters-information/">Headquarter Offices</a>
            </li>
            <li>
              <a href="/people-locations/organizational-chart/"
                >Organizational Chart</a
              >
            </li>
            <li>
              <a href="https://axon.ars.usda.gov/AFM/"
                >Employee Services (REE Employees Only)</a
              >
            </li>
            <li>
              <a href="/office-of-outreach-diversity-and-equal-opportunity/"
                >Office of Outreach, Diversity, and Equal Opportunity</a
              >
            </li>
          </ul>
        </li>
        <li>
          <button
            class="usa-accordion__button usa-nav-link"
            aria-expanded="false"
            aria-controls="side-nav-4"
            id="btnMenu-4"
          >
            <span id="menuName">Work With Us</span>
          </button>
          <ul
            id="side-nav-4"
            class="usa-nav-submenu usa-color-white"
            aria-hidden="true"
          >
            <li><a href="/work-with-us/">Work With Us Home</a></li>
            <li>
              <a href="https://arscareers.usajobs.gov/"
                >ARS Vacancies at USAJOBS</a
              >
            </li>
            <li>
              <a href="/careers/careers-at-ars-info/"
                >Careers at ARS Information</a
              >
            </li>
            <li><a href="/ott/">Scientific Collaborations</a></li>
            <li>
              <a href="/afm/fmad/agreements/agreements-home/"
                >Extramural Agreements</a
              >
            </li>
            <li>
              <a href="/research/1890-faculty-research-sabbatical-program/"
                >1890 Faculty Research Sabbatical Program</a
              >
            </li>
          </ul>
        </li>
      </ul>
      <form
        class="usa-search usa-search-small"
        method="get"
        action="https://search.usa.gov/search?sort=rel"
      >
        <div role="search">
          <label class="usa-sr-only" for="search-field-small"
            >Search small</label
          >
          <input id="query" type="search" name="query" title="search" />
          <!--title="search" added-->
          <input
            name="affiliate"
            id="affiliate"
            type="hidden"
            value="agriculturalresearchservicears"
          />
          <button type="submit">
            <span class="usa-sr-only">Search</span>
          </button>
        </div>
      </form>
      <!-- Secondary Naivagation Start -->
      <div class="usa-nav-secondary">
        <ul class="usa-unstyled-list usa-nav-secondary-links">
          <li>
            <a title="ARS Home" href="/"><b>ARS Home</b></a>
          </li>
          <li>
            <a title="About ARS" href="/about-ars/"><b>About ARS</b></a>
          </li>
          <li>
            <a title="Contact Us" href="/contact-us/?modeCode=00-00-00-00"
              ><b>Contact Us</b></a
            >
          </li>
        </ul>
      </div>
      <!-- Secondary Naivagation End -->
    </nav>
  </div>
</header>
