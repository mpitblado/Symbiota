<?php
include_once('../../config/symbini.php');
header("Content-Type: text/html; charset=".$CHARSET);
?>
<html>
	<head>
		<title>TDWG 2021 Gabon Biodiversity Poster</title>
		<?php
		$activateJQuery = false;
		include_once($SERVER_ROOT.'/includes/head.php');
		?>
	</head>
	<body>
		<?php
		$displayLeftMenu = true;
		include($SERVER_ROOT.'/includes/header.php');
		?>
		<div class="navpath">
			<a href="<?php echo $CLIENT_ROOT; ?>/index.php">Home</a> &gt;&gt;
			<b>TDWG 2021 Gabon Biodiversity Poster</b>
		</div>
		<!-- This is inner text! -->
		<div id="innertext">
			<h2>Creating a National Biodiversity Database in Gabon and the Challenges of Mobilizing Natural History Data for Francophone Countries</h2>
			<div style="">
				Tobi E, Aymar Nziengui Djiembi G, Feistner AT, Midoko Iponga D, Liwouwou JF, Mabala C, Mavoungou J, Moussavou G, Omouendze LP, Gilbert E, Jongsma GF (2021)
				Creating a National Biodiversity Database in Gabon and the Challenges of Mobilizing Natural History Data for Francophone Countries.
				Biodiversity Information Science and Standards 5: e75643. <a href="https://doi.org/10.3897/biss.5.75643" target="_blank">https://doi.org/10.3897/biss.5.75643</a>
			</div>
			<div style="margin-top:30px">
				<iframe src="PS_75643_TOBI.pdf" width="100%" height="500px"></iframe>
			</div>
			<div style="margin-top:20px">
				<video width="320" height="240" controls>
					<source src="TDWG_EN_FR_comp.mp4" type="video/mp4" />
					Your browser does not support the video tag.
				</video>
			</div>
			<hr style="margin: 20px 0px" />
			<div style="margin-top:20px">
				<h2>Occurrence Mapping Demostration</h2>
				<div style="margin:10px 0px">
					Following video presents a brief demostration of how occurrences can be visualized within a dynamic mapping interface.
				</div>
				<video controls="controls" width="100%" height="600" name="Occurrence Mapping Demostration">
					<source src="mapping_video.mov" />
					Your browser does not support the video tag.
				</video>
			</div>
		</div>
		<?php
		include($SERVER_ROOT.'/includes/footer.php');
		?>
	</body>
</html>
