<?php
$user = $SOUP->get('user');
// $tasks = $SOUP->get('tasks');

$fork = $SOUP->fork();

$fork->set('pageTitle', 'Profile');
$fork->set('headingURL', Url::user($user->getID()));
$fork->startBlockSet('body');

?>

<td class="left">

<?php
	$SOUP->render('site/partial/profile', array(
		'title' => $user->getUsername().'\'s Profile'
	));
?>

<?php
	$SOUP->render('site/partial/projects', array(
		'hasPermission' => false,
		'title' => $user->getUsername().'\'s Projects',
		'user' => $user
	));
?>

<?php
// 	$SOUP->render('project/partial/tasks', array(
// 		'user' => $user,
// 		'tasks' => $tasks,
// 		'hasPermission' => false,
// 		'title' => $user->getUsername().'\'s Tasks'		
// 	));
?>

</td>

<td class="right">

<?php
	$SOUP->render('site/partial/activity', array(
		'size' => 'small',
		'showProject' => true,
		'class' => 'subtle',
		'title' => $user->getUsername().'\'s Recent Activity'		
	));
?>

</td>



<?php

$fork->endBlockSet();
$fork->render('site/partial/page');