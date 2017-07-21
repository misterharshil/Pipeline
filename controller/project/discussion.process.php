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

//$token = Filter::alphanum($_POST['token']);
$action = Filter::text($_POST['action']);

if($action == 'lock') {
	$discussionID = Filter::numeric($_POST['discussionID']);
	$discussion = Discussion::load($discussionID);
	
	// make sure discussion exists
	if(empty($discussion)) {
		$json = array( 'error' => 'That discussion does not exist.' );
		exit(json_encode($json));	
	}
	
	// toggle lock
	if($discussion->getLocked()) {
		$discussion->setLocked(false);
		$eventTypeID = 'unlock_discussion';
		$successMessage = 'You unlocked the discussion.';
	} else {
		$discussion->setLocked(true);
		$eventTypeID = 'lock_discussion';
		$successMessage = 'You locked the discussion.';
	}
	$discussion->save();
	
	// log it
	$logEvent = new Event(array(
		'event_type_id' => $eventTypeID,
		'project_id' => $project->getID(),
		'user_1_id' => Session::getUserID(),
		'item_1_id' => $discussion->getID()
	));
	$logEvent->save();	
	
	// send us back
	Session::setMessage($successMessage);
	$json = array('success' => '1');
	echo json_encode($json);
	
} elseif($action == 'create') {
	// get additional POST variables
	$title = Filter::text($_POST['title']);
	$message = Filter::formattedText($_POST['message']);
	$cat = Filter::numeric($_POST['cat']);
	
	// validate
	if($title == '') {
		$json = array( 'error' => 'You must provide a title.' );
		exit(json_encode($json));	
	} elseif($message == '') {
		$json = array( 'error' => 'You must provide some text for the message.' );
		exit(json_encode($json));		
	}
	
	if($cat == '') $cat = null;
	
	// create discussion
	$discussion = new Discussion(array(
		'creator_id' => Session::getUserID(),
		'project_id' => $project->getID(),
		'title' => $title,
		'message' => $message,
		'category' => $cat
	));
	$discussion->save();
	// assign parent_id to self
	$discussion->setParentID($discussion->getID());
	$discussion->save();
	
	// attach any uploads
	// Upload::attachToItem(
		// $token,
		// Upload::TYPE_DISCUSSION,
		// $discussion->getID(),
		// $project->getID()
	// );
	
	// log it
	$logEvent = new Event(array(
		'event_type_id' => 'create_discussion',
		'project_id' => $project->getID(),
		'user_1_id' => Session::getUserID(),
		'item_1_id' => $discussion->getID(),
		'data_1' => $title,
		'data_2' => $message
	));
	$logEvent->save();
	
	// we're done here
	Session::setMessage('You created a new discussion.');
	// if category is set, redirect back to other section
	switch($cat) {
		case BASICS_ID:
			$successURL = Url::details($project->getID());
			break;
		case TASKS_ID:
			$successURL = Url::tasks($project->getID());
			break;
		case PEOPLE_ID:
			$successURL = Url::people($project->getID());
			break;
		case ACTIVITY_ID:
			$successURL = Url::activity($project->getID());
			break;
		default:
			$successURL = Url::discussion($discussion->getID());
	}
	$json = array('success' => '1', 'successUrl' => $successURL);
	echo json_encode($json);
	
} elseif($action == 'reply') {
	$discussionID = Filter::numeric($_POST['discussionID']);
	$message = Filter::formattedText($_POST['message']);
	
	if($message == '') {
		$json = array( 'error' => 'Your reply can not be blank.' );
		exit(json_encode($json));	
	}
	
	$discussion = Discussion::load($discussionID);
	
	$reply = new Discussion(array(
		'creator_id' => Session::getUserID(),
		'project_id' => $discussion->getProjectID(),
		'parent_id' => $discussion->getID(),
		'title' => $discussion->getTitle(),
		'message' => $message,
		'category' => $discussion->getCategory()
	));
	$reply->save();
	
	// attach any uploads
	// Upload::attachToItem(
		// $token,
		// Upload::TYPE_DISCUSSION,
		// $reply->getID(),
		// $project->getID()
	// );	
	
	// log it
	$logEvent = new Event(array(
		'event_type_id' => 'create_discussion_reply',
		'project_id' => $discussion->getProjectID(),
		'user_1_id' => Session::getUserID(),
		'item_1_id' => $reply->getID(),
		'item_2_id' => $discussion->getID(),
		'data_1' => $message
	));
	$logEvent->save();
	
	// send email notification, if desired
	
	// discussion creator
	$creator = User::load($discussion->getCreatorID());
	if($creator->getID() != Session::getUserID()) { // don't email yourself
		if($creator->getNotifyDiscussionStarted()) {
			// compose email
			$body = "<p>".formatUserLink(Session::getUserID()).' replied to your discussion <a href="'.Url::discussion($discussionID).'">'.$discussion->getTitle().'</a> in the project '.formatProjectLink($project->getID()).'. The reply was:</p>';
			$body .= "<blockquote>".formatDiscussionReply($message)."</blockquote>";
			$email = array(
				'to' => $creator->getEmail(),
				'subject' => '['.PIPELINE_NAME.'] New reply to your discussion in '.$project->getTitle(),
				'message' => $body
			);
			// send email
			Email::send($email);	
		}		
	}		
	
	// others who replied to discussion
	$repliers = $discussion->getDistinctRepliers();
	foreach($repliers as $r) {
		if($r->getID() != Session::getUserID()) { // don't email yourself
			if($r->getNotifyDiscussionReply()) {
				// compose email
				$body = "<p>".formatUserLink(Session::getUserID()).' replied to the discussion <a href="'.Url::discussion($discussionID).'">'.$discussion->getTitle().'</a> in the project '.formatProjectLink($project->getID()).'. The reply was:</p>';
				$body .= "<blockquote>".formatDiscussionReply($message)."</blockquote>";
				$email = array(
					'to' => $r->getEmail(),
					'subject' => '['.PIPELINE_NAME.'] New reply to a discussion in '.$project->getTitle(),
					'message' => $body
				);
				// send email
				Email::send($email);			
			}
		}
	}
	
	$json = array( 'success' => '1' );
	Session::setMessage("You replied to the discussion.");
	echo json_encode($json);
} else {
	$json = array( 'error' => 'Invalid action.' );
	exit(json_encode($json));	
}