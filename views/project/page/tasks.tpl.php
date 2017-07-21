<?php

$project = $SOUP->get('project');
$unclaimedTasks = $SOUP->get('unclaimedTasks');
$moreTasks = $SOUP->get('moreTasks');
$yourTasks = $SOUP->get('yourTasks');
$closedTasks = $SOUP->get('closedTasks');
$tasks = $SOUP->get('tasks');
$events = $SOUP->get('events');

$fork = $SOUP->fork();

$fork->set('project', $project);
$fork->set('pageTitle', $project->getTitle());
$fork->set('headingURL', Url::project($project->getID()));

$fork->set('selected', "tasks");
$fork->set('breadcrumbs', Breadcrumbs::tasks($project->getID()));
$fork->startBlockSet('body');

?>

<td class="left">

<?php if(Session::isLoggedIn()): ?>

<?php
	$SOUP->render('project/partial/tasks', array(
		'id' => 'yourTasks',
		'tasks' => $yourTasks,
		'title' => 'Your Tasks',
		'user' => Session::getUser()
	));
?>
    
<?php
        $SOUP->render('project/partial/tasks',array(
                'id' => 'unclaimedTasks',
                'tasks' => $unclaimedTasks,
                'title' => 'Unclaimed Tasks',
                'hasPermission' => false
        ));
?>

<?php
	$SOUP->render('project/partial/tasks', array(
		'id' => 'closedTasks',
		'tasks' => $closedTasks,
		'title' => 'Closed Tasks',
		'hasPermission' => false
	));
?>   

<?php else: ?>



<?php endif; ?>

</td>

<td class="right">


<?php
	$SOUP->render('project/partial/discussions',array(
		'title' => 'Recent Discussions',
		'cat' => 'tasks',
		'size' => 'small',
		'class' => 'subtle'
	));
?>

<?php
	$SOUP->render('site/partial/activity', array(
		'title' => "Recent Activity",
		'events' => $events,
		'size' => 'small',
		'olderURL' => Url::activityTasks($project->getID()),
		'class' => 'subtle'
		));
?>

</td>


<?php

$fork->endBlockSet();
$fork->render('site/partial/page');

?>