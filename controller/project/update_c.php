<?phprequire_once("../../global.php");$slug = Filter::text($_GET['slug']);$project = Project::getProjectFromSlug($slug);// kick us out if slug invalidif($project == null) {	header('Location: '.Url::error());	exit();}// validate task$taskID = Filter::numeric($_GET['t']);$task = Task::load($taskID);if($task == null) {	header('Location: '.Url::error());	exit();}// validate update$updateID = Filter::numeric($_GET['u']);$update = Update::load($updateID);if($update == null) {	header('Location: '.Url::error());	exit();}// if private project, limit access to invited users, members, and admins// and exclude banned membersif($project->getPrivate()) {	if (!Session::isAdmin() && (!$project->isCreator(Session::getUserID()))) {		if (((!$project->isInvited(Session::getUserID())) && (!$project->isMember(Session::getUserID())) &&		(!$project->isTrusted(Session::getUserID()))) || ProjectUser::isBanned(Session::getUserID(),$project->getID())) {		 	header('Location: '.Url::error());			exit();				}	}}// get update comments$comments = Comment::getByUpdateID($update->getID());// get events$events = Event::getUpdateEvents($update->getID(), 5);// get uploads$uploads = Upload::getByUpdateID($updateID, false);// $username = Filter::text($_GET['u']);// $user = User::loadByUsername($username);// check if user has accepted task$accepted = Accepted::load($update->getAcceptedID());// if($accepted == null) {	// header('Location: '.Url::error());	// exit();// }// get other updates$updates = $accepted->getUpdates();$filteredUpdates = array();foreach($updates as $u) {	if($u->getID() != $updateID) {		array_push($filteredUpdates, $u);	}}//$updates = Update::getByAcceptedID($accepted->getID());// $events = Event::getUpdatesEvents($accepted->getID(), 10);//$updates = Update::getByUserID($user->getID(), $taskID);$soup = new Soup();$soup->set('project', $project);$soup->set('task', $task);$soup->set('updates', $filteredUpdates);$soup->set('update', $update);//$soup->set('user', $user);$soup->set('uploads', $uploads);$soup->set('accepted', $accepted);$soup->set('events', $events);$soup->set('comments', $comments);$soup->render('project/page/update');