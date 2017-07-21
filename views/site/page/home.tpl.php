<?php
require_once TEMPLATE_PATH.'/site/helper/format.php';

$theme = Theme::load(DEFAULT_THEME_ID);
// set up stylesheet variables
$jqueryuiStylesheet = $theme->getJqueryuiStylesheet();
$pipelineStylesheet = $theme->getPipelineStylesheet();

?>
<!DOCTYPE html>
<html class="home">
<head>
	<title><?= PIPELINE_NAME ?> : Home</title>
	<link rel="icon" type="image/png" href="<?= Url::images() ?>/icons/clapperboard.png" />
	<script type="text/javascript" src="<?= Url::scripts() ?>/modernizr.js"></script>
	<link rel="stylesheet" type="text/css" href="<?= Url::styles() ?>/basic.css" />
	<link rel="stylesheet" type="text/css" href="<?= Url::styles() ?>/animate.css" />
	<link rel="stylesheet" type="text/css" href="<?= Url::styles() ?>/<?= $pipelineStylesheet ?>" />
	<link rel="stylesheet" type="text/css" href="<?= Url::styles() ?>/<?= $jqueryuiStylesheet ?>" />
		
	<script type="text/javascript" src="http://www.google.com/jsapi"></script>
	<script type="text/javascript"> 
		google.load("jquery", "1");
		google.load("jqueryui", "1.8.16");
		google.setOnLoadCallback(function(){});
	</script>
</head>
<body>

	<div class="top">

		<div class="funnel">
		<div class="animated bounceInDown">
		<h1><?= PIPELINE_NAME ?></h1>
		</div>
		</div>

	</div><!-- .top -->

	<div class="middle">

		<div class="funnel">
		<div class="animated bounceInLeft">
			<h2>Pipeline is a new way to create together on the Web. <a href="https://en.wikipedia.org/wiki/Crowdsourcing">Learn more</a></h2>
	
			<div class="line"></div>
			
			<div class="get-started">
				<p style="font-size: 120%;">First time here?</p>
				<a href="<?= Url::register() ?>">Get Started</a>
				
				<p>Already registered?</p>
				<a href="<?= Url::logIn() ?>">Log In</a>
			</div>		
			
			<div class="features">
	
				<h3>Lead projects your way</h3>
				<p>Easily get Crowd to work for you and get done with huge projects!</p>
	
				<h3>Volunteer-friendly</h3>
				<p>Find ways to contribute that match your interests, abilities, and available time.</p>
	
				<h3>Get creative, fast</h3>
				<p>Share and review video, audio, images, and animation, all from within your browser.</p>
	
			</div><!-- .features -->
	
			<div class="line"></div>
			
			<div class="meta">
				<p>Lightning fast & Open Source</p>
				<p>Web-based software works in any modern browser</p>
				<p>Developed by <a href="http://harshil.xyz"> Harshil Parikh </a> as a College project</p>
			</div><!-- .meta -->
	
			
			<?php
				// $SOUP->render('site/partial/activity', array(
					// 'size' => 'large',
					// 'showProject' => true,
					// 'title' => 'Recent Activity in '.PIPELINE_NAME
				// ));
			?>
	
		</div>
		</div>
	
	</div><!-- .middle -->

	<div class="bottom">
		<div class="funnel"> <div class="animated bounceInUp">
		<center>	Pipe - Crowd Sourcing Framework 2016 &copy; </center>
		</div>
		</div>
	</div>


</body>
</html>