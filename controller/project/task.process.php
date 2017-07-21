<?php
require_once('./../../global.php');
require_once TEMPLATE_PATH.'/site/helper/format.php'; 

// check project
$slug = Filter::text($_GET['slug']);
$project = Project::getProjectFromSlug($slug);
if($project == null) {
	$json = array('error' => 'That project does not exist.');
	exit(json_encode($json));	
}

$action = Filter::text($_POST['action']);

if ( ($action == 'create') || ($action == 'edit') ) {
	//$token = Filter::alphanum($_POST['token']);
	$title = Filter::text($_POST['txtTitle']);
//	$leaderName = Filter::alphanum($_POST['txtLeader']);
        $leaderName = Filter::usernameFilter($_POST['txtLeader']);
	$description = Filter::text($_POST['txtDescription']);
	$status = Filter::numeric($_POST['selStatus']);
	$numNeeded = Filter::numeric($_POST['txtNumNeeded']);
	$deadline = Filter::text($_POST['txtDeadline']);

	// validate the data
	
	// required fields
	if($title == '') {
		$json = array('error' => 'You must provide a name for this task.');
		exit(json_encode($json));
	} elseif($leaderName == '') {
		$json = array('error' => 'This task must have a leader.');
		exit(json_encode($json));		
	} elseif($description == '') {
		$json = array('error' => 'You must provide some instructions for this task.');
		exit(json_encode($json));
	}
	
	// leader must be real, and a creator or organizer
	$leader = User::loadByUsername($leaderName);
	if($leader === null){
		$json = array('error' => 'The user you specified to lead this task does not exist.');
		exit(json_encode($json));
	} elseif( !ProjectUser::isCreator($leader->getID(), $project->getID()) &&
		!ProjectUser::isTrusted($leader->getID(), $project->getID()) ) {
		$json = array('error' => 'Only the project creator or a trusted member may lead tasks.');
		exit(json_encode($json));
	}
	
	// num needed must be numeric or empty
	if( ($numNeeded != '') && (!is_numeric($numNeeded)) ) {
		$json = array('error' => 'Number of people needed must be a valid number or empty (for unlimited).');
		exit(json_encode($json));
	}
	
	// check for valid date
	$formattedDeadline = strtotime($deadline);
	if( ($formattedDeadline === false) && ($deadline != '') ) {
		$json = array('error' => 'Deadline must be a valid date or empty.');
		exit(json_encode($json));
	}
}

if ( ($action == 'edit') || ($action == 'accept') || ($action == 'release') || ($action == 'comment') || ($action == 'comment-reply') ) {
	// instantiate and validate task
	$taskID = Filter::numeric($_GET['t']);
	$task = Task::load($taskID);
	
	if($task == null) {
		$json = array('error' => 'That task does not exist.');
		exit(json_encode($json));	
	}
}

if($action == 'create') {
	// create task
	// first the required stuff
	$task = new Task(array(
		'creator_id' => Session::getUserID(),		
		'leader_id' => $leader->getID(),
		'project_id' => $project->getID(),
		'title' => $title,
		'description' => $description,
		'status' => $status
	));
	// now the optional stuff
	if($formattedDeadline !== false) {
		$formattedDeadline = date("Y-m-d H:i:s", $formattedDeadline);
		$task->setDeadline($formattedDeadline);
	}
	if($numNeeded != '')
		$task->setNumNeeded($numNeeded);
	$task->save();
	
	// save uploaded files to database
	foreach($_POST['file'] as $stored => $orig) {
		$stored = Filter::text($stored);
		$orig = Filter::text($orig);
		Upload::saveToDatabase(
			$orig,
			$stored,
			Upload::TYPE_TASK,
			$task->getID(),
			$project->getID()
			);
	}
	
	// log it
	$logEvent = new Event(array(
		'event_type_id' => 'create_task',
		'project_id' => $project->getID(),
		'user_1_id' => Session::getUserID(),
		'item_1_id' => $task->getID(),
		'data_1' => $task->getTitle(),
		'data_2' => $task->getDescription()
	));
	$logEvent->save();
	
	// we're done here
	Session::setMessage('You created a new task.');
	$json = array('success' => '1', 'successUrl' => Url::task($task->getID()));
	echo json_encode($json);
} elseif($action == 'edit') {
	// flag default is false; assume nothing is modified to start
	$modified = false;
	
	// is title modified?
	if($title != $task->getTitle()) {
		// save changes
		$oldTitle = $task->getTitle();
		$task->setTitle($title);
		$task->save();
		// log it
		$logEvent = new Event(array(
			'event_type_id' => 'edit_task_title',
			'project_id' => $project->getID(),
			'user_1_id' => Session::getUserID(),
			'item_1_id' => $task->getID(),
			'data_1' => $oldTitle,
			'data_2' => $title
		));
		$logEvent->save();
		// set flag
		$modified = true;
	}
	
	// is leader modified?
	if($leader->getID() != $task->getLeaderID()) {
		// save changes
		$oldLeaderID = $task->getLeaderID();
		$task->setLeaderID($leader->getID());
		$task->save();
		// log it
		$logEvent = new Event(array(
			'event_type_id' => 'edit_task_leader',
			'project_id' => $project->getID(),
			'user_1_id' => Session::getUserID(),
			'user_2_id' => $leader->getID(),
			'item_1_id' => $task->getID(),
			'data_1' => $oldLeaderID,
			'data_2' => $leader->getID()
		));
		$logEvent->save();
		
		// if changing the leader to someone besides you
		if($leader->getID() != Session::getUserID()) {
			// notify new leader, if applicable
			if($leader->getNotifyMakeTaskLeader()) {
				// compose email
				$body = "<p>".formatUserLink(Session::getUserID()).' made you the leader of the task <a href="'.Url::task($taskID).'">'.$task->getTitle().'</a> in the project '.formatProjectLink($project->getID()).'.</p>';
				$email = array(
					'to' => $leader->getEmail(),
					'subject' => '['.PIPELINE_NAME.'] You are now leading a task in '.$project->getTitle(),
					'message' => $body
				);
				// send email
				Email::send($email);				
			}		
		}
		
		// set flag
		$modified = true;		
	}
	
	// is description modified?
	if($description != $task->getDescription()) {
		// save changes
		$oldDescription = $task->getDescription();
		$task->setDescription($description);
		$task->save();
		// log it
		$logEvent = new Event(array(
			'event_type_id' => 'edit_task_description',
			'project_id' => $project->getID(),
			'user_1_id' => Session::getUserID(),
			'item_1_id' => $task->getID(),
			'data_1' => $oldDescription,
			'data_2' => $description
		));
		$logEvent->save();
		// set flag
		$modified = true;	
	}
	
	// is status modified?
	if($status != $task->getStatus()) {
		// save changes
		$oldStatus = $task->getStatus();
		$task->setStatus($status);
		$task->save();
		// log it
		$logEvent = new Event(array(
			'event_type_id' => 'edit_task_status',
			'project_id' => $project->getID(),
			'user_1_id' => Session::getUserID(),
			'item_1_id' => $task->getID(),
			'data_1' => $oldStatus,
			'data_2' => $status
		));
		$logEvent->save();
		// set flag
		$modified = true;		
	}
	
	// is num needed modified?
	if($numNeeded != $task->getNumNeeded()) {
		// save changes
		$oldNumNeeded = $task->getNumNeeded();
		$task->setNumNeeded($numNeeded);
		$task->save();
		// log it
		$logEvent = new Event(array(
			'event_type_id' => 'edit_task_num_needed',
			'project_id' => $project->getID(),
			'user_1_id' => Session::getUserID(),
			'item_1_id' => $task->getID(),
			'data_1' => $oldNumNeeded,
			'data_2' => $numNeeded
		));
		$logEvent->save();
		// set flag
		$modified = true;		
	}
	
	// is deadline modified?
	$formattedDeadline = ($formattedDeadline != '') ? date("Y-m-d H:i:s", $formattedDeadline) : null;
	$oldDeadline = $task->getDeadline();
	if($formattedDeadline != $oldDeadline) {
		// save changes
		$task->setDeadline($formattedDeadline);
		$task->save();
		// log it
		$logEvent = new Event(array(
			'event_type_id' => 'edit_task_deadline',
			'project_id' => $project->getID(),
			'user_1_id' => Session::getUserID(),
			'item_1_id' => $task->getID(),
			'data_1' => $oldDeadline,
			'data_2' => $formattedDeadline
		));
		$logEvent->save();
		// set flag
		$modified = true;		
	}
	
	// get posted vars for attached files
	$deleted = $_POST['deleted']; // deleted files
	$added = $_POST['file']; // added files
	
	// are uploads deleted?
	if(!empty($deleted)) {
		$deletedIDs = '';
		foreach($deleted as $d) {
			// save changes
			$d = Filter::numeric($d);
			$upload = Upload::load($d);
			$upload->setDeleted(true);
			$upload->save();
			$deletedIDs .= $d.',';
		}
	}
	
	// are uploads added?
	if(!empty($added)) {
		$addedIDs = '';
		foreach($added as $stored => $orig) {
			// save changes
			$stored = Filter::text($stored);
			$orig = Filter::text($orig);
			$uploadID = Upload::saveToDatabase(
				$orig,
				$stored,
				Upload::TYPE_TASK,
				$task->getID(),
				$project->getID()
				);
			$addedIDs .= $uploadID.',';
		}
	}
	
	// deal with logging and modified flag for both adds and deletes
	if(!empty($deletedIDs) || !empty($addedIDs)) {
		// log it
		$logEvent = new Event(array(
			'event_type_id' => 'edit_task_uploads',
			'user_1_id' => Session::getUserID(),
			'project_id' => $project->getID(),
			'item_1_id' => $task->getID(),
			'data_1' => $deletedIDs,
			'data_2' => $addedIDs
		));
		$logEvent->save();
		// set flag
		$modified = true;	
	}
	
	// check flag
	if($modified) {
		// notify task crew, if desired
		$crew = Accepted::getByTaskID($task->getID());
		if($crew != null) {
			foreach($crew as $c) {
				$user = User::load($c->getCreatorID());
				if($user->getID() != Session::getUserID()) { // don't email yourself
					if($user->getNotifyEditTaskAccepted()) {
						// compose email
						$body = "<p>".formatUserLink(Session::getUserID()).' edited the task <a href="'.Url::task($taskID).'">'.$task->getTitle().'</a> in the project '.formatProjectLink($project->getID()).'.</p>';
						$email = array(
							'to' => $user->getEmail(),
							'subject' => '['.PIPELINE_NAME.'] New edits to a task you joined in '.$project->getTitle(),
							'message' => $body
						);
						// send email
						Email::send($email);				
					}
				}
			}
		}
		Session::setMessage('You edited this task.');
		$json = array('success' => '1', 'successUrl' => Url::task($task->getID()));
		echo json_encode($json);		
	} else {
		$json = array('error' => 'No changes were detected.');
		exit(json_encode($json));	
	}
} elseif($action == 'accept') {
	// join user to project, if they're not already
	$pu = ProjectUser::find(Session::getUserID(), $project->getID());
	if(empty($pu)) {
		// not a project member yet, so make them one
		$pu = new ProjectUser(array(
			'project_id' => $project->getID(),
			'user_id' => Session::getUserID(),
			'relationship' => ProjectUser::MEMBER
		));
		$pu->save();
		
		// log it
		$logEvent = new Event(array(
			'event_type_id' => 'join_project',
			'project_id' => $project->getID(),
			'user_1_id' => Session::getUserID()
		));
		$logEvent->save();			
	} elseif($project->isFollower(Session::getUserID())) {
		// convert follower to member
		$pu->setRelationship(ProjectUser::MEMBER);	
		$pu->save();
		
		// log it
		$logEvent = new Event(array(
			'event_type_id' => 'join_project',
			'project_id' => $project->getID(),
			'user_1_id' => Session::getUserID()
		));
		$logEvent->save();		
	}

	// accept the task
	$accepted = new Accepted(array(
		'creator_id' => Session::getUserID(),
		'project_id' => $project->getID(),
		'task_id' => $taskID,
		'status' => Accepted::STATUS_PROGRESS
	));
	$accepted->save();
	
	// log it
	$logEvent = new Event(array(
		'event_type_id' => 'accept_task',
		'project_id' => $project->getID(),
		'user_1_id' => Session::getUserID(),
		'item_1_id' => $accepted->getID(),
		'item_2_id' => $taskID
	));
	$logEvent->save();
	
	// send us back
	Session::setMessage('You joined the task. Good luck!');
	$json = array('success' => '1', 'successUrl' => Url::task($taskID));
	echo json_encode($json);
} elseif($action == 'release') {
	$accepted = Accepted::getByUserID(Session::getUserID(), $taskID);
	if(!empty($accepted)) {
		$accepted->setStatus(Accepted::STATUS_RELEASED);
		$accepted->save();
		
		// log it
		$logEvent = new Event(array(
			'event_type_id' => 'release_task',
			'project_id' => $project->getID(),
			'user_1_id' => Session::getUserID(),
			'item_1_id' => $accepted->getID(),
			'item_2_id' => $taskID
		));
		$logEvent->save();
		
		// send us back
		Session::setMessage('You left the task.');
		$json = array('success' => '1', 'successUrl' => Url::tasks($project->getID()));
		echo json_encode($json);
	} else {
		$json = array('error' => 'You never joined that task.');
		exit(json_encode($json));	
	}
} elseif($action == 'comment') {
	$message = Filter::formattedText($_POST['message']);
	if($message == '') {
		$json = array('error' => 'Your comment cannot be empty.');
		exit(json_encode($json));		
	} else {
		// post the comment
		$comment = new Comment(array(
			'creator_id' => Session::getUserID(),
			'project_id' => $project->getID(),
			'task_id' => $taskID,
			'message' => $message
		));
		$comment->save();
		// re-save now that we have an ID
		$comment->setParentID($comment->getID());
		$comment->save();
		
		// log it
		$logEvent = new Event(array(
			'event_type_id' => 'create_task_comment',
			'project_id' => $project->getID(),
			'user_1_id' => Session::getUserID(),
			'item_1_id' => $comment->getID(),
			'item_2_id' => $taskID,
			'data_1' => $message
		));
		$logEvent->save();
		
		// send email notifications, if applicable
		
		// to task leader
		$leader = User::load($task->getLeaderID());
		if($leader->getID() != Session::getUserID()) { // don't email yourself
			if($leader->getNotifyCommentTaskLeading()) {
				// compose email
				$body = "<p>".formatUserLink(Session::getUserID()).' commented on the task <a href="'.Url::task($taskID).'">'.$task->getTitle().'</a> in the project '.formatProjectLink($project->getID()).'. The comment was:</p>';
				$body .= "<blockquote>".formatComment($message)."</blockquote>";
				$email = array(
					'to' => $leader->getEmail(),
					'subject' => '['.PIPELINE_NAME.'] New comment on a task you are leading in '.$project->getTitle(),
					'message' => $body
				);
				// send email
				Email::send($email);	
			}	
		}
		
		// to task crew
		$crew = Accepted::getByTaskID($taskID);
		if($crew != null) {
			foreach($crew as $c) {
				$user = User::load($c->getCreatorID());
				if($user->getID() != Session::getUserID()) { // don't email yourself
					if($user->getNotifyCommentTaskAccepted()) {
						// compose email
						$body = "<p>".formatUserLink(Session::getUserID()).' commented on the task <a href="'.Url::task($taskID).'">'.$task->getTitle().'</a> in the project '.formatProjectLink($project->getID()).'. The comment was:</p>';
						$body .= "<blockquote>".formatComment($message)."</blockquote>";
						$email = array(
							'to' => $user->getEmail(),
							'subject' => '['.PIPELINE_NAME.'] New comment on a task you joined in '.$project->getTitle(),
							'message' => $body
						);
						// send email
						Email::send($email);				
					}
				}
			}
		}		
		
		// send us back
		Session::setMessage('You commented on this task.');
		$json = array('success' => '1');
		echo json_encode($json);
	}
} elseif($action == 'comment-reply') {
	$commentID = Filter::numeric($_POST['commentID']);
	$message = Filter::formattedText($_POST['message']);
	if($message == '') {
		$json = array('error' => 'Your reply cannot be empty.');
		exit(json_encode($json));		
	} else {
		// post the comment
		$reply = new Comment(array(
			'creator_id' => Session::getUserID(),
			'project_id' => $project->getID(),
			'task_id' => $taskID,
			'parent_id' => $commentID,
			'message' => $message
		));
		$reply->save();	
		
		// log it
		$logEvent = new Event(array(
			'event_type_id' => 'create_task_comment_reply',
			'project_id' => $project->getID(),
			'user_1_id' => Session::getUserID(),
			'item_1_id' => $commentID,
			'item_2_id' => $reply->getID(),
			'item_3_id' => $taskID,
			'data_1' => $message
		));
		$logEvent->save();
		
		// send email notifications, if applicable
		
		// to task leader
		$leader = User::load($task->getLeaderID());
		if($leader->getID() != Session::getUserID()) { // don't email yourself
			if($leader->getNotifyCommentTaskLeading()) {
				// compose email
				$body = "<p>".formatUserLink(Session::getUserID()).' replied to a comment on the task <a href="'.Url::task($taskID).'">'.$task->getTitle().'</a> in the project '.formatProjectLink($project->getID()).'. The reply was:</p>';
				$body .= "<blockquote>".formatComment($message)."</blockquote>";
				$email = array(
					'to' => $leader->getEmail(),
					'subject' => '['.PIPELINE_NAME.'] New comment reply on a task you are leading in '.$project->getTitle(),
					'message' => $body
				);
				// send email
				Email::send($email);	
			}		
		}
		
		// to task crew
		$crew = Accepted::getByTaskID($taskID);
		if($crew != null) {
			foreach($crew as $c) {
				$user = User::load($c->getCreatorID());
				if($user->getID() != Session::getUserID()) { // don't email yourself
					if($user->getNotifyCommentTaskAccepted()) {
						// compose email
						$body = "<p>".formatUserLink(Session::getUserID()).' replied to a comment on the task <a href="'.Url::task($taskID).'">'.$task->getTitle().'</a> in the project '.formatProjectLink($project->getID()).'. The reply was:</p>';
						$body .= "<blockquote>".formatComment($message)."</blockquote>";
						$email = array(
							'to' => $user->getEmail(),
							'subject' => '['.PIPELINE_NAME.'] New comment reply on a task you joined in '.$project->getTitle(),
							'message' => $body
						);
						// send email
						Email::send($email);				
					}
				}
			}
		}			
		
		// send us back
		Session::setMessage('You replied to a comment on this task.');
		$json = array('success' => '1');
		echo json_encode($json);
	}	
} else {
	$json = array('error' => 'Invalid action.');
	exit(json_encode($json));
}