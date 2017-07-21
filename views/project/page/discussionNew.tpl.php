<?php
$project = $SOUP->get('project');
$yourDiscussions = $SOUP->get('yourDiscussions');

$fork = $SOUP->fork();

$fork->set('project', $project);
$fork->set('pageTitle', $project->getTitle());
$fork->set('headingURL', Url::project($project->getID()));

$fork->set('selected', "discussions");
$fork->set('breadcrumbs', Breadcrumbs::discussionNew($project->getID()));

$fork->startBlockSet('body');
?>

<td class="left">

<?php
	$SOUP->render('project/partial/discussionNew', array(
		));
?>

</td>

<td class="right">

<?php
	$SOUP->render('project/partial/discussions',array(
		'discussions' => $yourDiscussions,
		'size' => 'small',
		'title' => 'Your Discussions',
		'hasPermission' => false
	));
?>

</td>


<?php

$fork->endBlockSet();
$fork->render('site/partial/page');

?>