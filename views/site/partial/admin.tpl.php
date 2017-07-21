<?php

require_once TEMPLATE_PATH.'/site/helper/format.php';

$body = $SOUP->get('body');
$pageTitle = $SOUP->get('pageTitle');
$headingURL = $SOUP->get('headingURL', '#');
$selected = $SOUP->get('selected', null);
$breadcrumbs = $SOUP->get('breadcrumbs', null);
$projects = $SOUP->get('projects', null);
$users = $SOUP->get('users',null);

if(Session::isLoggedIn()) {
	$user = Session::getUser();
	// update last login
	$user->setLastLogin(date("Y-m-d H:i:s"));
	$user->save();
	// load unread messages
	$numUnread = $user->getNumUnreadMessages();
	// load custom theme, if specified
	if($user->getThemeID() != null) {
		$theme = Theme::load($user->getThemeID());
	} else {
		$theme = Theme::load(DEFAULT_THEME_ID); // load default theme
	}
} else {
	$theme = Theme::load(DEFAULT_THEME_ID); // load default theme
}

// set up stylesheet variables
$jqueryuiStylesheet = $theme->getJqueryuiStylesheet();
$pipelineStylesheet = $theme->getPipelineStylesheet();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<title><?= PIPELINE_NAME ?> - <?= $pageTitle ?></title>
	<link rel="icon" type="image/png" href="<?= Url::images() ?>/icons/clapperboard.png" />
	<link rel="stylesheet" type="text/css" href="<?= Url::styles() ?>/basic.css" />
	<link rel="stylesheet" type="text/css" href="<?= Url::styles() ?>/<?= $pipelineStylesheet ?>" />
	<link rel="stylesheet" type="text/css" href="<?= Url::styles() ?>/<?= $jqueryuiStylesheet ?>" />
	<script type="text/javascript" src="http://www.google.com/jsapi"></script>
	<script type="text/javascript"> 
		google.load("jquery", "1");
		google.load("jqueryui", "1.8.16");
		google.setOnLoadCallback(function(){});
	</script>
	<script type="text/javascript" src="<?= Url::scripts() ?>/common.js"></script>
	<script type="text/javascript" src="<?= Url::scripts() ?>/feedback.js"></script>
	<?php if(Session::getMessage() != null): ?>
	<script type="text/javascript">
		$(document).ready(function(){
			displayNotification("<?= Session::getMessage() ?>");
		});
	</script>		
	<?php Session::clearMessage(); ?>
	<?php endif; ?>
</head>
<body>

<div class="page-header">
	<div class="primary-nav">
		<div class="funnel">
		<div class="animated bounceInDown">
			<h1><a href="<?= Url::base() ?>"><?= PIPELINE_NAME ?></a></h1>
			<ul>
			<?php if(Session::isLoggedIn()): ?>
				<li class="right"><a href="<?= Url::logOut() ?>">Log Out</a></li>
				<li class="right"><a href="<?= Url::settings() ?>">Settings</a></li>	
				<li class="right"><a href="<?= Url::inbox() ?>">Inbox<?= ($numUnread>0) ? '<span class="unread">'.$numUnread.'</span>' : '' ?></a></li>		
				<li class="right"><a href="<?= Url::profile() ?>"><?= Session::getUsername() ?></a></li>				
			<?php else: ?>
				<li class="right"><a href="<?= Url::consent() ?>">Register</a></li>
				<li class="right"><a href="<?= Url::logIn() ?>">Log In</a></li>
			<?php endif; ?>
				<li class="left"><a href="<?= Url::projectNew() ?>">Start a Project</a></li>			
				<li class="left"><a href="<?= Url::findProjects() ?>">Find Projects</a></li>				
				<li class="left"><a href="<?= Url::help() ?>">Help</a></li>
			<?php if(Session::isAdmin()): ?>
				<li class="left"><a href="<?= Url::admin() ?>">Admin</a></li>
			<?php endif; ?>			
			</ul>
			</div>
		</div><!-- end .funnel -->	
	</div><!-- end .primary-nav -->
	<div class="funnel">
		<div class="heading">
				
			<h2>
				<a href="<?= $headingURL ?>"><?= $pageTitle ?></a>
				
			</h2>
		</div><!-- end .funnel -->
	</div><!-- end .heading -->
<?php if($selected != null): ?>
	<div class="funnel">
		<div class="secondary-nav">
			<ul>
			                       
				<li><a <?= ($selected == "recentActivity")?'class="selected"':'' ?> href="<?= Url::admin()?>">Recent Activity</a></li>
			<!--	<li><a <?//= ($selected == "utilities")?'class="selected"':'' ?> href="<?//= Url::utilities() ?>">Utilities</a></li> -->
			
			</ul>
		</div><!-- end .secondary-nav -->
	</div><!-- end .funnel -->
<?php endif; ?>
</div><!-- end .page-header -->
<div class="page-body">
	<div class="funnel">
                        
            <table id="columns">
		<tr><?= $body ?></tr>
            </table>
              
	</div><!-- end .funnel -->
</div><!-- end .page-body -->
<div class="page-footer">
	<div class="funnel">
	<div class="animated bounceInUp">
		<center>	Pipe - Crowd Sourcing Framework 2016 &copy; </center>
		</div>
	</div>
</div>
<div id="feedback"></div><!-- #feedback -->

</body>
</html>
