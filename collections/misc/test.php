<!DOCTYPE HTML>
<html>
<head>
    <title>ARS Home : USDA ARS</title>
    <?php
    include_once('../../config/symbini.php');
	include_once($SERVER_ROOT . '/includes/head.php');
	include_once($SERVER_ROOT . '/includes/googleanalytics.php');
	?>

    <meta http-equiv='Content-Type:text/html; charset=UTF-8' />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />			<!-- disable IE compatibility view -->
    <meta name="description" />
    <meta name="keywords" content="" />
    <meta key="ID" value="1075" />

    <!-- include jQuery library -->
    


    <!-- photo carsouel -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>

    <meta name='bbsect' content='Main'>
    <meta name='modestem' content='00a00b00c00d'>
    <meta name='modestem1' content='00a'>
    <meta name='modestem2' content='00b'>
    <meta name='modestem3' content='00c'>
    <meta name='modestem4' content='00d'>
    <meta name='site_code' content='00-00-00-00'>

        <meta name="WT.ModeCode" content="00-00-00-00">


    
<!-- Custom Page Header Scripts -->

    



    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Research : USDA ARS</title>
    <!-- Update the link path to where your stylesheet file is located. For example: /path/to/your/USWDS/css/lib/uswds.min.css -->
    <!-- <link rel="stylesheet" href="/USWDS/css/uswds.css"> -->
    <link rel="stylesheet" href="<?php echo htmlspecialchars($CSS_BASE_PATH, HTML_SPECIAL_CHARS_FLAGS); ?>/symbiota/styleguide.css">
    <link href="<?php echo htmlspecialchars($CSS_BASE_PATH, HTML_SPECIAL_CHARS_FLAGS); ?>/symbiota/ARSbranding.css" type="text/css" rel="stylesheet">
    <script type="text/javascript" src="../../js/ARSbranding.js"></script>
    <link media="print" href="/css/print.css" rel="stylesheet" type="text/css" />
    <script type="text/javascript" src="//s7.addthis.com/js/300/addthis_widget.js#pubid=arsociowb"></script>
</head>

<body class="layout-demo" role="banner">

    

<a class="usa-skipnav" href="#main-content">Skip to main content</a>
<header class="usa-header usa-header-basic" role="banner">
    <!-- Gov banner BEGIN -->
    <div class="usa-banner">
        <div class="usa-accordion">
            <header class="usa-banner-header">
                <div class="usa-grid usa-banner-inner">
                    <img
                        aria-hidden="true"
                        class="usa-banner__header-flag"
                        src="<?php echo $CLIENT_ROOT ?>/assets/uswds/img/us_flag_small.png"
                        alt=""
                    />
                    <p>An official website of the United States government</p>
                    <button class="usa-accordion-button usa-banner-button"
                            aria-expanded="false" aria-controls="gov-banner">
                        <span class="usa-banner-button-text">Here's how you know</span>
                    </button>
                </div>
            </header>
            <div class="usa-banner-content usa-grid usa-accordion-content" id="gov-banner">
                <div class="usa-banner-guidance-gov usa-width-one-half">
                    <!-- <img class="usa-banner-icon usa-media_block-img" src="<?php echo $CLIENT_ROOT ?>/assets/uswds/img/icon-dot-gov.svg" alt="Dot gov"> -->
                    <img
                        class="usa-banner-icon usa-media_block-img"
                        src="<?php  echo $CLIENT_ROOT ?>/assets/uswds/img/icon-dot-gov.svg"
                        role="img"
                        alt="Dot gov"
                        aria-hidden="true"
                    />
                    <div class="usa-media_block-body">
                        <p>
                            <strong>Official websites use .gov</strong>
                            <br>
                            A <strong>.gov</strong> website belongs to an official government organization in the United States.
                        </p>
                    </div>
                </div>
                <div class="usa-banner-guidance-ssl usa-width-one-half">
                    <img class="usa-banner-icon usa-media_block-img" src="<?php echo $CLIENT_ROOT ?>/assets/uswds/img/icon-https.svg" alt="SSL">
                    <div class="usa-media_block-body">
                        <p>
                            <strong>Secure .gov websites use HTTPS</strong>
                            <br>
                            A <strong>lock</strong> (                            
                              <span class="icon-lock"><svg xmlns="http://www.w3.org/2000/svg" width="52" height="64" viewBox="0 0 52 64" class="usa-banner__lock-image" role="img" aria-labelledby="banner-lock-title banner-lock-description"><title id="banner-lock-title">Lock</title><desc id="banner-lock-description">A locked padlock</desc><path fill="#000000" fill-rule="evenodd" d="M26 0c10.493 0 19 8.507 19 19v9h3a4 4 0 0 1 4 4v28a4 4 0 0 1-4 4H4a4 4 0 0 1-4-4V32a4 4 0 0 1 4-4h3v-9C7 8.507 15.507 0 26 0zm0 8c-5.979 0-10.843 4.77-10.996 10.712L15 19v9h22v-9c0-6.075-4.925-11-11-11z"></path></svg></span>                                                      
                            
                            ) or <strong>https://</strong> means you’ve safely connected to the .gov website. Share sensitive information only on official, secure websites.

                           
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

                <span href="https://www.usda.gov" title="Home" rel="home" class="usda-logo">
                    <img src="<?php echo $CLIENT_ROOT ?>/assets/uswds/img/USDA-Logo.png" alt="USDA Logo" class="usda-logo-img">
                </span>

                <em class="usa-logo-text">
                    <a href="https://www.ars.usda.gov/" title="Agricultural Research Service Home" 
							aria-label="The Agricultural Research Service Home Page" 
							rel="home" id="anch_1">Agricultural Research Service
					</a>
						
                    <h6>
						<a href="https://www.usda.gov/" title="U.S. DEPARTMENT OF AGRICULTURE Home" 
							aria-label="The U.S. Department of Agriculture Home Page" 
							rel="home" id="anch_1">U.S. DEPARTMENT OF AGRICULTURE
						</a>						
					</h6>
                </em>
            </div>
        </div>


        <!-- Logo  and Log-Text End -->
        <nav role="navigation" class="usa-nav usa-color-primary-darkest">
            
            <button class="usa-nav-close">
                <img src="<?php echo $CLIENT_ROOT ?>/assets/uswds/img/usa-icons/close.svg" alt="close">
            </button>



<ul class="usa-nav-primary usa-accordion">
            <li>
                <button class="usa-accordion-button usa-nav-link" aria-expanded="false" aria-controls="side-nav-1" id="btnMenu-1">
                    <span id="menuName">Research</span>
                </button>
                <ul id="side-nav-1" class="usa-nav-submenu usa-color-white" aria-hidden="true">
                            <li><a href="/research/">Research Home</a></li>
                            <li><a href="/research/programs/">National Programs</a></li>
                            <li><a href="/research/projects/">Research Projects</a></li>
                            <li><a href="/research/publications/find-a-publication/">Scientific Manuscripts</a></li>
                            <li><a href="/office-of-international-research-engagement-and-cooperation/office-of-international-research-engagement-and-cooperation/">International Engagement</a></li>
                            <li><a href="/research/software/">Scientific Software/Models</a></li>
                            <li><a href="/research/datasets/">Databases and Datasets</a></li>
                            <li><a href="/office-of-scientific-quality-review-osqr/">Office of Scientific Quality Review</a></li>
                </ul>
            </li>
            <li>
                <button class="usa-accordion-button usa-nav-link" aria-expanded="false" aria-controls="side-nav-2" id="btnMenu-2">
                    <span id="menuName">Media</span>
                </button>
                <ul id="side-nav-2" class="usa-nav-submenu usa-color-white" aria-hidden="true">
                            <li><a href="/news-events/news-events/">News &amp; Features</a></li>
                            <li><a href="/oc/ars-wired/">Multimedia</a></li>
                            <li><a href="/oc/br/briefing-room/">Briefing Room</a></li>
                            <li><a href="/oc/dof/archive/">Down on the Farm</a></li>
                            <li><a href="/oc/press-room/">Press Room</a></li>
                            <li><a href="/oc/scienceinyourshoppingcart/siysc-factsheets">Science in Your Shopping Cart</a></li>
                            <li><a href="https://scientificdiscoveries.ars.usda.gov">Scientific Discoveries</a></li>
                            <li><a href="https://tellus.ars.usda.gov/">Tellus</a></li>
                            <li><a href="/oc/utm/archive">Under the Microscope</a></li>
                </ul>
            </li>
            <li>
                <button class="usa-accordion-button usa-nav-link" aria-expanded="false" aria-controls="side-nav-3" id="btnMenu-3">
                    <span id="menuName">About ARS</span>
                </button>
                <ul id="side-nav-3" class="usa-nav-submenu usa-color-white" aria-hidden="true">
                            <li><a href="/about-ars/">About ARS Home</a></li>
                            <li><a href="/people-locations/find-a-person/">Staff Directory</a></li>
                            <li><a href="/people-locations/find-a-location/">Labs and Research Centers (Map)</a></li>
                            <li><a href="/docs/headquarters-information/">Headquarter Offices</a></li>
                            <li><a href="/people-locations/organizational-chart/">Organizational Chart</a></li>
                            <li><a href="https://axon.ars.usda.gov/AFM/">Employee Services (REE Employees Only)</a></li>
                            <li><a href="/office-of-outreach-diversity-and-equal-opportunity/">Office of Outreach, Diversity, and Equal Opportunity</a></li>
                </ul>
            </li>
            <li>
                <button class="usa-accordion-button usa-nav-link" aria-expanded="false" aria-controls="side-nav-4" id="btnMenu-4">
                    <span id="menuName">Work With Us</span>
                </button>
                <ul id="side-nav-4" class="usa-nav-submenu usa-color-white" aria-hidden="true">
                            <li><a href="/work-with-us/">Work With Us Home</a></li>
                            <li><a href="https://arscareers.usajobs.gov/">ARS Vacancies at USAJOBS</a></li>
                            <li><a href="/careers/careers-at-ars-info/">Careers at ARS Information</a></li>
                            <li><a href="/ott/">Scientific Collaborations</a></li>
                            <li><a href="/afm/fmad/agreements/agreements-home/">Extramural Agreements</a></li>
                            <li><a href="/research/1890-faculty-research-sabbatical-program/">1890 Faculty Research Sabbatical Program</a></li>
                </ul>
            </li>

</ul>
            <form class="usa-search usa-search-small" method="get" action="https://search.usa.gov/search?sort=rel">
                <div role="search">
                    <label class="usa-sr-only" for="search-field-small">Search small</label>
                    <input id="query" type="search" name="query" title="search"> <!--title="search" added-->
                    <input name="affiliate" id="affiliate" type="hidden" value="agriculturalresearchservicears">
                    <button type="submit">
                        <span class="usa-sr-only">Search</span>
                    </button>
                </div>
            </form>
            <!-- Secondary Naivagation Start -->
            <div class="usa-nav-secondary" >
                <ul class="usa-unstyled-list usa-nav-secondary-links">
                                <li><a title="ARS Home" href="/"><b>ARS Home</b></a></li>
                                <li><a title="About ARS" href="/about-ars/"><b>About ARS</b></a></li>
                                <li><a title="Contact Us" href="/contact-us/?modeCode=00-00-00-00"><b>Contact Us</b></a></li>

                </ul>
            </div>
            <!-- Secondary Naivagation End -->
        </nav>

    </div>
</header>



    <div class="usa-overlay"></div>

    <main class="usa-grid usa-section usa-content usa-layout-docs" id="main-content">

        



<link rel="stylesheet" href="/css/photoCarousel.css?t=20231009" />
<!-- <link rel="stylesheet" href="~/css/layout.css" /> -->

<style>
     .usa-section {
         padding-top: 0rem;
         padding-left: 0rem;
         padding-right: 0rem;
     }

     #leftCustomContent {
         position: relative;
         float: left;
         width: 50%;
         margin: 0;
         padding: 0;
         border-right: 5px solid white;
         height: auto;
     }

     #rightCustomContent {
         margin-left: 10px;
     }

     .whiteLinkColor a {
         color: #ffffff;
     }

    .whiteLinkColor a:visited {
	   color: #ffffff;
    }


</style>

<script type="text/javascript">
    $(document).ready(function () {
        var url = null;

        $('#iWantToButton').click(function (event) {
            url = $("#iWantToDropdownlist").val();
            window.location.href = url;
        });

        $('#trendingTopicsButton').click(function (event) {
            url = $("#trendingTopicsDropdownlist").val();
            window.location.href = url;
        });

        $('#stateReportButton').click(function (event) {
            url = $("#stateReportDropdownlist").val();
            window.location.href = url;
        });

        $('#hqOfficesButton').click(function (event) {
            url = $("#hqOfficesDropdownlist").val();
            window.location.href = url;
        });	  
    });

</script>

<main id="main-content">
    <!-- Photo Carousel -->
    
<!-- photo carousel -->
<a name="photos" class="section508">Photo Carousel Links</a>
<div id="carousel" class="carousel slide" data-ride="carousel" data-interval="8000">
    <!-- Wrapper for slides -->
        <div class="carousel-inner usa-color-primary">
                <div class="item active">
                    <div class="holder col-sm-8 ars-carousel-container">
                        <a href="/oc/images/photos/featuredphoto/oct23/pumpkins/" title="Pumpkins and Indian corn." >
                            <img class="img-responsive"
                                 src="/ARSUserFiles/00000000/images/PhotoCarousel/d4740-5c.jpg"
                                 alt="Pumpkins and Indian corn."
                                 id="ars-carousel-image" />
                        </a>
                    </div>

                    <div class="col-sm-4">
                        <div class="carousel-caption whiteLinkColor">
                            <h2></h2>
                            <p class="usa-color-white">
                                <p align="left"><strong>October Featured Photo</strong>:</p>
<p align="left">Pumpkins are a Halloween favorite! Squash bees are perfect pumpkin pollinators. <a href="/oc/images/photos/featuredphoto/oct23/pumpkins/">Download this photo</a> and learn more.</p>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="item ">
                    <div class="holder col-sm-8 ars-carousel-container">
                        <a href="https://www.ars.usda.gov/research/lectures/index/" title="ARS 2023 Memorial Lecturers" >
                            <img class="img-responsive"
                                 src="/ARSUserFiles/00000000/NPS/Lectures/Carousel/Lecturers_2023.png"
                                 alt="ARS 2023 Memorial Lecturers"
                                 id="ars-carousel-image" />
                        </a>
                    </div>

                    <div class="col-sm-4">
                        <div class="carousel-caption whiteLinkColor">
                            <h2></h2>
                            <p class="usa-color-white">
                                <h3 align="left"><span>ARS Memorial Lecturers</span></h3>
<p align="left">The Agricultural Research Service's Memorial Lectureships recognize scientists who have made outstanding contributions to agriculture. </p>
<p align="left">Congratulations to this year's winners.</p>
<p align="left"><a data-id="242867" href="/research/lectures/index/" title="index">Learn more</a></p>
                            </p>
                        </div>
                    </div>
                </div>
        </div>

        <!--<div class="controllers col-sm-8 col-xs-12">-->
        <!-- Controls- role button for the Iphone plus mobiles -->
        <div class="left carousel-control" href="#carousel" data-slide="prev" role="button">
            <span class="glyphicon glyphicon-chevron-left"></span>
        </div>
        <div class="right carousel-control" href="#carousel" data-slide="next" role="button">
            <span class="glyphicon glyphicon-chevron-right"></span>
        </div>
        <div class="controllers">
            <!-- Indicators -->
            <ol class="carousel-indicators">
                    <li data-target="#carousel"
                        data-slide-to="0"
                        class="myCarousel-target active"></li>
                    <li data-target="#carousel"
                        data-slide-to="1"
                        class="myCarousel-target "></li>
            </ol>
        </div>
</div>

    <!-- middle content -->
    <section class="usa-grid usa-section">
        <div class="usa-grid-full">
		  <!-- I want to-->
            <div class="usa-width-one-half">
                <h3>I Want To</h3>
                <div class="ars-dropdownlist-container">
                        <select id="iWantToDropdownlist">
                            <option selected="selected" value="#">- choose -</option>

                                <option value="https://axon.ars.usda.gov/">Find Axon (REE Employees Only)</option>
                                <option value="/people-locations/find-a-person/">Find a person</option>
                                <option value="/people-locations/find-a-location/">Find a location</option>
                                <option value="/northeast-area/beltsville-md-bhnrc/beltsville-human-nutrition-research-center/food-surveys-research-group/docs/main-service-page/">Get nutrition information</option>
                                <option value="/research/projects/">Find a research project</option>
                                <option value="/research/publications/find-a-publication/">Find a scientific manuscript</option>
                                <option value="/work-with-us/">Work with ARS</option>
                        </select>
				    <input name="iWantToButton" id="iWantToButton" type="button" value="Go" class="ars-dropdownlist-button" />
                </div>
            </div>

		  <!--Trending Topics -->
            <div class="dropdown-TrendingTopics usa-width-one-half">
                <h3>Trending Topics</h3>
                <div class="ars-dropdownlist-container">

                            <select id="trendingTopicsDropdownlist">
                                <option selected="selected" value="#">- choose -</option>

								<option value="https://axon.ars.usda.gov/Pages/Intranet%20Home.aspx">Axon (REE Employees Only)</option>
								<option value="/oc/br/ccd/index/">Bees</option>
								<option value="https://search.usa.gov/search?affiliate=agriculturalresearchservicears&amp;query=biochar">Biochar</option>
								<option value="https://search.usa.gov/search?affiliate=agriculturalresearchservicears&amp;query=calcium">Calcium</option>
								<option value="/trending-topics/?topic=Corn">Corn</option>
								<option value="/trending-topics/?topic=Fiber">Fiber</option>
								<option value="https://search.usa.gov/search?affiliate=agriculturalresearchservicears&amp;query=orac">ORAC</option>
								<option value="https://search.usa.gov/search?affiliate=agriculturalresearchservicears&amp;query=safe+food">Safe Food</option>
								<option value="/trending-topics/?topic=Seeds">Seeds</option>
								<option value="https://search.usa.gov/search?affiliate=agriculturalresearchservicears&amp;query=Vitamin+K">Vitamin K</option>
                            </select>
                            <input name="trendingTopicsButton" id="trendingTopicsButton" type="button" value="Go" class="ars-dropdownlist-button" />
                </div>
            </div>
        </div>

	   <!-- news story images-->
        <div class="ars-grid-image usa-grid">

                <div class="usa-width-one-half">
                    <ars-news-story-container>
                        <ars-news-story>
                            <a href="https://aglab.ars.usda.gov/">
                                <img src="/ARSUserFiles/00000000/images/NewsStoryPhotos/AGLab.jpg" alt="Science for Growing Minds" title="Science for Growing Minds">
                                <imagecaption>Science for Growing Minds </imagecaption>
                            </a>

                        </ars-news-story>
                    </ars-news-story-container>
                </div>
                            <div class="usa-width-one-half">
                    <ars-news-story-container>
                        <ars-news-story>
                            <a href="https://www.ars.usda.gov/news-events/news/research-news/2023/scientists-ratchet-up-key-amino-acid-in-corn/">
                                <img src="/ARSUserFiles/00000000/images/NewsStoryPhotos/k9803-1.jpg" alt="Scientists Increase Key Amino Acid in Corn" title="Scientists Increase Key Amino Acid in Corn">
                                <imagecaption>Scientists Increase Key Amino Acid in Corn </imagecaption>
                            </a>
                        </ars-news-story>
                    </ars-news-story-container>
                </div>
            </div>
                <div class="usa-grid-full-image">
                    <ars-news-story-container>
                        <ars-news-story>
                            <a href="https://tellus.ars.usda.gov/stories/articles/solving-mysteries-behind-worlds-most-widespread-zoonotic-disease">
                                <img src="/ARSUserFiles/00000000/images/NewsStoryPhotos/Lepto.jpg" alt="Featured Tellus Content: Solving the Mysteries Behind the World’s Most Widespread Zoonotic Disease" title="Featured Tellus Content: Solving the Mysteries Behind the World’s Most Widespread Zoonotic Disease">
                                <imagecaption>Featured Tellus Content: Solving the Mysteries Behind the World’s Most Widespread Zoonotic Disease </imagecaption>
                            </a>
                        </ars-news-story>
                    </ars-news-story-container>
                </div>
                            <div class="usa-grid-full-image">
                    <ars-news-story-container>
                        <ars-news-story>
                            <a href="https://www.ars.usda.gov/oc/ars-wired/">
                                <img src="/ARSUserFiles/00000000/images/NewsStoryPhotos/ARSWired.png" alt="." title=".">
                                <imagecaption>.</imagecaption>
                            </a>
                        </ars-news-story>
                    </ars-news-story-container>
                </div>


        <div class="usa-grid-full">
            <!-- ARS research in your state-->
            <div class="usa-width-one-half">
                <h3>ARS Research In Your State</h3>
                <div class="ars-dropdownlist-container">
                    <select name="stateReportDropdownlist" id="stateReportDropdownlist">
                        <option selected="selected" value="">- choose -</option>

                                        <option value="/state/?id=AL">Alabama</option>
                                        <option value="/state-without-research-locations/">Alaska</option>
                                        <option value="/state/?id=AZ">Arizona</option>
                                        <option value="/state/?id=AR">Arkansas</option>
                                        <option value="/state/?id=CA">California</option>
                                        <option value="/state/?id=CO">Colorado</option>
                                        <option value="/state-without-research-locations/">Connecticut</option>
                                        <option value="/state/?id=DE">Delaware</option>
                                        <option value="/state/?id=DC">District of Columbia</option>
                                        <option value="/state/?id=FL">Florida</option>
                                        <option value="/state/?id=GA">Georgia</option>
                                        <option value="/state/?id=HI">Hawaii</option>
                                        <option value="/state/?id=ID">Idaho</option>
                                        <option value="/state/?id=IL">Illinois</option>
                                        <option value="/state/?id=IN">Indiana</option>
                                        <option value="/state/?id=IA">Iowa</option>
                                        <option value="/state/?id=KS">Kansas</option>
                                        <option value="/state/?id=KY">Kentucky</option>
                                        <option value="/state/?id=LA">Louisiana</option>
                                        <option value="/state/?id=ME">Maine</option>
                                        <option value="/state/?id=MD">Maryland</option>
                                        <option value="/state/?id=MA">Massachusetts</option>
                                        <option value="/state/?id=MI">Michigan</option>
                                        <option value="/state/?id=MN">Minnesota</option>
                                        <option value="/state/?id=MS">Mississippi</option>
                                        <option value="/state/?id=MO">Missouri</option>
                                        <option value="/state/?id=MT">Montana</option>
                                        <option value="/state/?id=NE">Nebraska</option>
                                        <option value="/state/?id=NV">Nevada</option>
                                        <option value="/state-without-research-locations/">New Hampshire</option>
                                        <option value="/docs/worksites/New-Jersey/">New Jersey</option>
                                        <option value="/state/?id=NM">New Mexico</option>
                                        <option value="/state/?id=NY">New York</option>
                                        <option value="/state/?id=NC">North Carolina</option>
                                        <option value="/state/?id=ND">North Dakota</option>
                                        <option value="/state/?id=OH">Ohio</option>
                                        <option value="/state/?id=OK">Oklahoma</option>
                                        <option value="/state/?id=OR">Oregon</option>
                                        <option value="/state/?id=PA">Pennsylvania</option>
                                        <option value="/state/?id=PR">Puerto Rico</option>
                                        <option value="/docs/worksites/Rhode-Island/">Rhode Island</option>
                                        <option value="/state/?id=SC">South Carolina</option>
                                        <option value="/state/?id=SD">South Dakota</option>
                                        <option value="/docs/worksites/Tennessee/">Tennessee</option>
                                        <option value="/state/?id=TX">Texas</option>
                                        <option value="/state/?id=UT">Utah</option>
                                        <option value="/state-without-research-locations/">Vermont</option>
                                        <option value="/state-without-research-locations/">Virginia</option>
                                        <option value="/state/?id=WA">Washington</option>
                                        <option value="/state/?id=WV">West Virginia</option>
                                        <option value="/state/?id=WI">Wisconsin</option>
                                        <option value="/docs/worksites/Wyoming/">Wyoming</option>
				</select>

				<input type="button" value="Go" id="stateReportButton" class="ars-dropdownlist-button" />
                </div>
            </div>

            <!-- Headquarters Offices-->
            <div class="usa-width-one-half">
                <h3>Headquarters Offices</h3>
                <div class="ars-dropdownlist-container">

					   <select id="hqOfficesDropdownlist">

						  <option selected="selected" value="#">- choose -</option>

							 <option value="/afm/">Administrative and Financial Management Home Page</option>
							 <option value="/people-locations/people-list-offices/?modeCode=03-00-00-00">Administrative and Financial Management Staff</option>
							 <option value="/people-locations/people-list-offices/?modeCode=01-01-15-00">Budget and Program Management Staff</option>
							 <option value="/afm/itsd/">Information Technology Services Home Page</option>
							 <option value="/people-locations/people-list-offices/?modeCode=03-28-00-00">Information Technology Services Staff</option>
							 <option value="/la/">Legislative Affairs Home Page</option>
							 <option value="/people-locations/people-list-offices/?modeCode=01-01-10-00">Legislative Affairs Staff</option>
							 <option value="/people-locations/people-list-offices/?modeCode=01-01-00-00">Office of the Administrator</option>
							 <option value="/news-events/news-events/">Office of Communications Home Page</option>
							 <option value="/people-locations/people-list-offices/?modeCode=04-04-00-00">Office of Communications Staff</option>
							 <option value="/office-of-outreach-diversity-and-equal-opportunity/">Office of Outreach, Diversity, and Equal Opportunity Home Page</option>
							 <option value="/people-locations/people-list-offices/?modeCode=01-01-20-00">Office of Outreach, Diversity, and Equal Opportunity Staff</option>
							 <option value="/office-of-scientific-quality-review-osqr/">Office of Scientific Quality Review Home Page</option>
							 <option value="/people-locations/people-list-offices/?modeCode=04-10-00-00">Office of Scientific Quality Review Staff</option>
							 <option value="/ott/">Office of Technology Transfer Home Page</option>
							 <option value="/people-locations/people-list-offices/?modeCode=04-02-00-00">Office of Technology Transfer Staff</option>

					   </select>
					   <input name="hqOfficesButton" id="hqOfficesButton" type="button" value="Go" class="ars-dropdownlist-button" />
                </div>                  
            </div>
        </div>

    </section>
</main>


        <!-- Digital Analytics (DAP) -->
        <!-- We participate in the US government's analytics program. See the data at analytics.usa.gov. -->
        <script async id="_fed_an_ua_tag" src="https://dap.digitalgov.gov/Universal-Federated-Analytics-Min.js?agency=USDA&subagency=ARS"></script>


        <!-- WebTrends 10 -->
        <!-- START OF SmartSource Data Collector TAG -->
        <!-- Copyright (c) 2018 Webtrends Inc.  All rights reserved. -->
        <!-- Version: 10.4.23 -->
        <!-- Tag Builder Version: 4.1.3.5  -->
        <!-- Created: 2018.07.11 -->
        <script src="/ARSUserFiles/Utility/WebTrends/webtrends.load_ars.js" type="text/javascript"></script>

        <noscript>
            <div>
                <img alt="DCSIMG" id="DCSIMG" width="1" height="1"
                     src="https://statse.webtrendslive.com/dcs2229fyq5yxf5j5aw1zdupi_2f9o/njs.gif?dcsuri=/nojavascript&amp;WT.js=No&amp;WT.tv=10.4.23&amp;dcssip=www.ars.usda.gov" />
            </div>
        </noscript>
        <!-- END OF SmartSource Data Collector TAG -->

        <script type="text/javascript">
            var _gaq = _gaq || [];
            _gaq.push(['_setAccount', 'UA-27627304-6']);
            _gaq.push(['_setDomainName', 'usda.gov']);
            _gaq.push(['_setAllowLinker', true]);
            _gaq.push(['_trackPageview']);

            _gaq.push(['b._setAccount', 'UA-27627304-1']);
            _gaq.push(['b._setDomainName', 'usda.gov']);
            _gaq.push(['b._setAllowLinker', true]);
            _gaq.push(['b._trackPageview']);

            (function () {
                var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
                ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
                var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
            })();
        </script>
				
				
	<!-- start-GA4-Google tag (gtag.js) -->
	<script async src="https://www.googletagmanager.com/gtag/js?id=G-ND298XG1VM"></script>
	<script>
  	window.dataLayer = window.dataLayer || [];
  	function gtag(){dataLayer.push(arguments);}
  	gtag('js', new Date());

  	gtag('config', 'G-ND298XG1VM');
	</script>
	<!-- end Google tag (gtag.js) -->			
				
				
    </main>

    

<footer class="usa-footer usa-footer-big" role="contentinfo">
    <!-- last modified date in documentation page -->

    <!--  Headquarter pages only -->
        <div class="usa-grid-full usa-color-gray-light">
            <nav class="usa-footer-nav usa-width-two-thirds" id="footer-top">
                <ul class="usa-unstyled-list">
                            <li class="usa-width-one-fourth usa-footer-primary-content"><a  class="usa-footer-primary-link" href="/research/">Research</a></li>
                            <li class="usa-width-one-fourth usa-footer-primary-content"><a  class="usa-footer-primary-link" href="/news-events/news-events/">Media</a></li>
                            <li class="usa-width-one-fourth usa-footer-primary-content"><a  class="usa-footer-primary-link" href="/about-ars/">About ARS</a></li>
                            <li class="usa-width-one-fourth usa-footer-primary-content"><a  class="usa-footer-primary-link" href="/work-with-us/">Work With Us</a></li>
                </ul>
            </nav>
        </div>

    <!-- middle footer -->
    <div class="usa-grid-full usa-color-gray-dark">
        <!-- Connect with ARS and Floating social Icons-->
        <nav class="usa-footer-nav usa-width-one-half">
             <nav class="usa-width-one-third" id="Connect-ARS">
                <h3 class="usa-sign_up-header">Connect with ARS</h3>
                

<!-- Connect with ARS Social icons in footer -->
    <div class="usa-footer-nav" id="socialLink">
                        <a href="http://twitter.com/usda_ars">
                            <img src="/ARSUserFiles/00000000/images/social_media///twitter.png" title="Twitter" alt="Twitter" />
                        </a>
                        <a href="https://www.linkedin.com/company/usda-ars">
                            <img src="/ARSUserFiles/00000000/images/social_media///linkedin.png" title="LinkedIn" alt="LinkedIn" />
                        </a>
                        <a href="https://www.youtube.com/channel/UCbY4NfKJTwEO1rxTdNGjYbA">
                            <img src="/ARSUserFiles/00000000/images/social_media///youtube.png" title="Youtube" alt="Youtube" />
                        </a>
                        <a href="https://www.facebook.com/AgriculturalResearchService">
                            <img src="/ARSUserFiles/00000000/images/social_media///square-facebook-icon.jpg" title="Facebook Icon" alt="Facebook Icon" />
                        </a>
    </div>

<!-- Floating Social shared icons-->
    <div class="float-ConnectwithARS">
        <div class="addthis_toolbox addthis_default_style addthis_32x32_style">
            <ul class="connectwithARS">
                <!--Start : Social shared icons-->
                <li><a class="addthis_button_twitter"></a></li>
                <li><a class="addthis_button_linkedin"></a> </li>
                <li><a class="addthis_button_facebook"></a> </li>
                <li><a class="addthis_button_email"></a></li>
                <li><a class="addthis_button_print"></a></li>
                <li><a class="addthis_button_compact"></a> </li>
                <!--End : Social shared icons-->
            </ul>
        </div>
    </div>

            </nav>
             <nav class="usa-width-one-fourth" id="TellUs">
                 <a href="https://tellus.ars.usda.gov/" title="Tellus"
                    aria-label="The Tellus Home Page"   rel="home">                  
                     <img src="/ARSUserFiles/00000000/images/social_media/tellus-logo.png" title="Tellus" alt="Tellus">
                 </a>
                
             </nav>
        </nav>

         <!-- Sign up for ARS News updates-->
        <nav class="usa-footer-nav usa-width-one-half">
            <form id="GD-snippet-form" action="https://public.govdelivery.com/accounts/USDAARS/subscriber/qualify?qsp=CODE_RED"
                  accept-charset="UTF-8" method="post" target="_blank">

                <input name="utf8" type="hidden" value="&#x2713;" />
                <input type="hidden" name="authenticity_token" value="tV2OquJR5xnmtrmmZS3UWsIp7QddNiZcKotw2AMMUx2u9nfu4b3aL1Fb4L6RnJCoF5VYhXZ85qUPjpOyJiUlhg==" />

                <fieldset>
                    <h3 class="usa-sign_up-header">Sign up</h3>
                    <div class="EmailSignup-Footer">
                        <!-- <input type="text" name="email" id="email" placeholder="Sign up for ARS News updates" style="display:inline-block;" value> <input type="submit" name="commit" class="form_button" id="go" value="Go" style="display:inline-block;" />-->
                        <input type="text" name="email" id="email" style="display:inline-block;" title="email" value> <input type="submit" name="commit" class="form_button" id="go" value="Go" style="display:inline-block;" />
                    </div>

                </fieldset>
            </form>

                    <!-- GovDelivery Subscription Overlay -->
                    <script src='https://content.govdelivery.com/overlay/js/6017.js'></script>
                    <!-- End GovDelivery Overlay -->
                    <!--CFI survey pop-up display section-->
                    <script src="/ARSUserFiles/Utility/WebMoniter/WebMoniter.js" type="text/javascript"></script>
                    <!--End CFI survey pop-up -->
            </nav>
           <!-- End Sign up for ARS News updates-->
</div>

    <!-- bottom footer-->
    <div class="usa-grid-full usa-color-gray-dark">
        <nav class="usa-footer-nav" id="footer-bottom">
            <ul class="usa-unstyled-list">
                            <li class="usa-width-one-fourth usa-footer-primary-content">
                                <a href="/">ARS Home</a>
                            </li>
                            <li class="usa-width-one-fourth usa-footer-primary-content">
                                <a href="http://www.usda.gov/">USDA.gov</a>
                            </li>
                            <li class="usa-width-one-fourth usa-footer-primary-content">
                                <a href="https://www.usda.gov/plain-writing">Plain Writing</a>
                            </li>
                            <li class="usa-width-one-fourth usa-footer-primary-content">
                                <a href="https://www.usda.gov/policies-and-links">Policies &amp; Links</a>
                            </li>
                            <li class="usa-width-one-fourth usa-footer-primary-content">
                                <a href="https://www.usda.gov/oascr/civil-rights-statements">Civil Rights Statements</a>
                            </li>
                            <li class="usa-width-one-fourth usa-footer-primary-content">
                                <a href="/research/freedom-of-information-act-and-privacy-act-reference-guide/">FOIA</a>

                            </li>
                            <li class="usa-width-one-fourth usa-footer-primary-content">
                                <a href="https://www.usda.gov/accessibility-statement">Accessibility Statement</a>

                            </li>
                            <li class="usa-width-one-fourth usa-footer-primary-content">
                                <a href="https://www.usda.gov/privacy-policy">Privacy Policy</a>

                            </li>
                            <li class="usa-width-one-fourth usa-footer-primary-content">
                                <a href="https://www.usda.gov/non-discrimination-statement">Non-Discrimination Statement</a>

                            </li>
                            <li class="usa-width-one-fourth usa-footer-primary-content">
                                <a href="/docs/quality-of-information/">Quality of Information</a>

                            </li>
                            <li class="usa-width-one-fourth usa-footer-primary-content">
                                <a href="https://www.usa.gov">USA.gov</a>

                            </li>
                            <li class="usa-width-one-fourth usa-footer-primary-content">
                                <a href="https://www.whitehouse.gov">WhiteHouse.gov</a>

                            </li>

            </ul>
        </nav>
    </div>
</footer>

    <!--[if lt IE 9]>
        <img class="IErounded" src="/images/redesign/rounded-bottomBLK.png" alt="spacer">
    <![endif]-->
    <!-- pageComplete -->
    
<!-- Custom Page Footers Scripts -->

    
    <script src="<?php echo htmlspecialchars($CLIENT_ROOT, HTML_SPECIAL_CHARS_FLAGS); ?>/assets/uswds/js/uswds-init.min.js" type="text/javascript"></script>
    <script src="/USWDS/js/uswds.min.js"></script>

</body>
</html>
