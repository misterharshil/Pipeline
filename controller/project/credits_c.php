<?php
require_once("../../global.php");

$slug = Filter::text($_GET['slug']);
$project = Project::getProjectFromSlug($slug);

// kick us out if slug invalid
if($project == null) {
	header('Location: '.Url::error());
	exit();
}

// if private project, limit access to invited users, members, and admins
// and exclude banned members
if($project->getPrivate()) {
	if (!Session::isAdmin() && (!$project->isCreator(Session::getUserID()))) {
		if (((!$project->isInvited(Session::getUserID())) && (!$project->isMember(Session::getUserID())) &&
		(!$project->isTrusted(Session::getUserID()))) || ProjectUser::isBanned(Session::getUserID(),$project->getID())) {
		 	header('Location: '.Url::error());
			exit();		
		}
	}
}

//$followers = $project->getFollowers();

$banned = $project->getBanned();

$allMembers = $project->getAllMembers();
$memberInvites = $project->getInvitations();

$discussions = Discussion::getPeopleDiscussionsByProjectID($project->getID(), 3);

$events = Event::getPeopleEventsByProjectID($project->getID(), 5);

$soup = new Soup();
$soup->set('project', $project);
$soup->set('allMembers', $allMembers);
$soup->set('memberInvites', $memberInvites);
$soup->set('banned', $banned);
//$soup->set('followers', $followers);
$soup->set('events', $events);
$soup->set('discussions', $discussions);

$soup->render('project/page/credits');