<?php

class ProjectUser extends DbObject
{
	protected $id;
	protected $userID;
	protected $projectID;
	protected $relationship;
	
	const DB_TABLE = 'project_user';
	
	const BANNED = 0;
	const FOLLOWER = 1;
	const MEMBER = 5;
	const TRUSTED = 10;
	const CREATOR = 101;
	
	// const TRUSTED = 1;
	// const UNTRUSTED = 0;
	//const ORGANIZER = 10;
	
	public function __construct($args=array())
	{
		$defaultArgs = array(
			'id' => null,
			'user_id' => 0,
			'project_id' => 0,
			'relationship' => 0
		);	
		
		$args += $defaultArgs;
		
		$this->id = $args['id'];
		$this->userID = $args['user_id'];
		$this->projectID = $args['project_id'];
		$this->relationship = $args['relationship'];
	}	
	
	public static function load($id)
	{
		$db = Db::instance();
		$obj = $db->fetch($id, __CLASS__, self::DB_TABLE);
		return $obj;
	}

	public function save()
	{
		$db = Db::instance();
		// map database fields to class properties; omit id and dateCreated
		$db_properties = array(
			'user_id' => $this->userID,
			'project_id' => $this->projectID,
			'relationship' => $this->relationship
		);		
		$db->store($this, __CLASS__, self::DB_TABLE, $db_properties);
	}
	
	public function delete() {
		$query = "DELETE from ".self::DB_TABLE;
		$query .= " WHERE user_id = ".$this->userID;
		$query .= " AND project_id = ".$this->projectID;
		
		$db = Db::instance();
		$db->execute($query);
		ObjectCache::remove(get_class($this),$this->id);
	}

	
// ---------------------------------------------------------------------------- //	
	
	
	public static function find($userID=null, $projectID=null) {
		if( ($userID === null) ||
			($projectID === null) ) {
			return null;
		}
		
		$query = "SELECT id FROM ".self::DB_TABLE;
		$query .= " WHERE user_id = ".$userID;
		$query .= " AND project_id = ".$projectID;
		
		$db = Db::instance();
		$result = $db->lookup($query);
		if(!mysql_num_rows($result))
			return null;
		elseif($row = mysql_fetch_assoc($result))
			return (self::load($row['id']));
	}
	
	// used on profile page
	public static function getProjectsByUserID($userID=null, $limit=null) {
		if($userID === null) return null;
		$loggedInUserID = Session::getUserID();
		
		$query = " SELECT pu.project_id AS id FROM ".self::DB_TABLE." pu";
		$query .= " INNER JOIN ".Project::DB_TABLE." p ON";
		$query .= " pu.project_id = p.id";
		$query .= " WHERE pu.user_id = ".$userID;
		$query .= " AND pu.relationship != ".self::BANNED;
		// only show private projects if logged-in user is also a member
		if(!empty($loggedInUserID)) {
			$query .= " AND (p.private = 0";
			$query .= " OR pu.project_id IN (";
				$query .= " SELECT project_id FROM ".self::DB_TABLE;
				$query .= " WHERE user_id = ".$loggedInUserID;
				$query .= " AND relationship != ".self::BANNED;
			$query .= " ))";
		} else {
			$query .= " AND p.private = 0";
		}
		$query .= " ORDER BY p.title ASC";
		if(!empty($limit))
			$query .= " LIMIT ".$limit;
		
		$db = Db::instance();
		$result = $db->lookup($query);
		if(!mysql_num_rows($result)) return array();

		$projects = array();
		while($row = mysql_fetch_assoc($result))
			$projects[$row['id']] = Project::load($row['id']);
		return $projects;	
	}

	public static function getAllMembers($projectID=null) {
		if($projectID === null) return null;
		
		$query = "SELECT user_id AS id FROM ".self::DB_TABLE." pu";
		$query .= " INNER JOIN ".User::DB_TABLE." u ON ";
		$query .= " pu.user_id = u.id";
		$query .= " WHERE pu.project_id = ".$projectID;
		$query .= " AND (pu.relationship = ".self::MEMBER;
		$query .= " OR pu.relationship = ".self::TRUSTED.')';
		$query .= " ORDER BY u.username ASC";	
		//echo $query.'<br />';
		
		$db = Db::instance();
		$result = $db->lookup($query);
		if(!mysql_num_rows($result)) return array();

		$users = array();
		while($row = mysql_fetch_assoc($result))
			$users[$row['id']] = User::load($row['id']);
		return $users;			
	}
	
	public static function getTrusted($projectID=null) {
		return(self::getByProjectID($projectID, self::TRUSTED));
	}
	
	public static function getMembers($projectID=null) {
		return(self::getByProjectID($projectID, self::MEMBER));
	}	
	
	public static function getFollowers($projectID=null) {
		return(self::getByProjectID($projectID, self::FOLLOWER));
	}

	public static function getBanned($projectID=null) {
		return(self::getByProjectID($projectID, self::BANNED));
	}
	
	public static function getBannableUsernames($projectID=null, $term=null) {
		if($projectID === null) return null;
		
		$query = "SELECT username FROM ".User::DB_TABLE;
		$query .= " WHERE id NOT IN (";
			$query .= " SELECT user_id FROM ".self::DB_TABLE;
			$query .= " WHERE project_id = ".$projectID;
			$query .= " AND relationship = ".self::BANNED; // can't be banned
			$query .= " OR relationship = ".self::CREATOR; // can't be creator
		$query .= " )";
		if(!empty($term))
			$query .= " AND username LIKE '%".$term."%'";
		$query .= " ORDER BY username ASC";
		
		$db = Db::instance();
		$result = $db->lookup($query);
		if(!mysql_num_rows($result)) return array();
		
		$usernames = array();
		while($row = mysql_fetch_assoc($result))
			$usernames[] = $row['username'];
		return $usernames;		
	}
	
	public static function getTrustedUsernames($projectID=null, $term=null) {
		if($projectID === null) return null;
		
		$query = "SELECT u.username AS username FROM ".User::DB_TABLE." u";	
		$query .= " INNER JOIN ".self::DB_TABLE." pu";
		$query .= " ON u.id = pu.user_id";
		$query .= " WHERE pu.project_id = ".$projectID;
		$query .= " AND (pu.relationship = ".self::TRUSTED;
		$query .= " OR pu.relationship = ".self::CREATOR.")";
		if(!empty($term))
			$query .= " AND u.username LIKE '%".$term."%'";		
		$query .= " ORDER BY u.username ASC";
		
		$db = Db::instance();
		$result = $db->lookup($query);
		
		if(!mysql_num_rows($result))
			return array();
		
		$usernames = array();
		while($row = mysql_fetch_assoc($result))
			$usernames[] = $row['username'];
		return $usernames;			
	}	
	
	public static function getUnaffiliatedUsernames($projectID=null, $term=null) {
		if($projectID === null) return null;
		
		$query = "SELECT username FROM ".User::DB_TABLE;	
		$query .= " WHERE id NOT IN (";
			$query .= " SELECT user_id FROM ".self::DB_TABLE;
			$query .= " WHERE project_id = ".$projectID;
		$query .= " )";
		if(!empty($term))
			$query .= " AND username LIKE '%".$term."%'";		
		$query .= " ORDER BY username ASC";
		
		$db = Db::instance();
		$result = $db->lookup($query);
		
		if(!mysql_num_rows($result))
			return array();
		
		$usernames = array();
		while($row = mysql_fetch_assoc($result))
			$usernames[] = $row['username'];
		return $usernames;			
	}		
	
	public static function getByProjectID($projectID=null, $relationship=null) {
		if($projectID == null) return null;
		
		$query = "SELECT user_id AS id FROM ".self::DB_TABLE." pu";
		$query .= " INNER JOIN ".User::DB_TABLE." u ON ";
		$query .= " pu.user_id = u.id";
		$query .= " WHERE pu.project_id = ".$projectID;
		if($relationship !== null) {
			$query .= " AND pu.relationship = ".$relationship;
		}
		$query .= " ORDER BY u.username ASC";	
		//echo $query.'<br />';
		
		$db = Db::instance();
		$result = $db->lookup($query);
		if(!mysql_num_rows($result)) return array();

		$users = array();
		while($row = mysql_fetch_assoc($result))
			$users[$row['id']] = User::load($row['id']);
		return $users;			
	}

	public static function isCreator($userID=null, $projectID=null) {
		return (self::hasRelationship($userID,$projectID,self::CREATOR));	
	}

	public static function isTrusted($userID=null, $projectID=null) {
		return (self::hasRelationship($userID,$projectID,self::TRUSTED));
	}
	
	public static function isMember($userID=null, $projectID=null) {
		return (self::hasRelationship($userID,$projectID,self::MEMBER));
	}
	
	public static function isFollower($userID=null, $projectID=null)
	{
		return (self::hasRelationship($userID,$projectID,self::FOLLOWER));
	}
	
	public static function isBanned($userID=null, $projectID=null)
	{
		return (self::hasRelationship($userID,$projectID,self::BANNED));
	}

	public static function isAffiliated($userID=null, $projectID=null) {
		return (self::hasRelationship($userID,$projectID));
	}
	
	// avoid calling this... use one of the aliased functions above instead
	public static function hasRelationship($userID=null, $projectID=null, $relationship=null) {
		if( ($userID === null) || ($projectID === null) ) return null;
		
		$query = "SELECT * FROM ".self::DB_TABLE;
		$query .= " WHERE user_id = ".$userID;
		$query .= " AND project_id = ".$projectID;
		if($relationship !== null)
			$query .= " AND relationship = ".$relationship;
		//echo $query;
		
		$db = Db::instance();
		$result = $db->lookup($query);
		if(!mysql_num_rows($result))
			return false;
		else
			return true;
	}

	
	// --- only getters and setters below here --- //	
	
	public function getID()
	{
		return ($this->id);
	}

	public function setID($newID)
	{
		$this->id = $newID;
		$this->modified = true;
	}	
	
	public function getUserID()
	{
		return ($this->userID);
	}
	
	public function setUserID($newUserID)
	{
		$this->userID = $newUserID;
		$this->modified = true;
	}
	
	public function getProjectID()
	{
		return ($this->projectID);
	}
	
	public function setProjectID($newProjectID)
	{
		$this->projectID = $newProjectID;
		$this->modified = true;	
	}
	
	public function getRelationship()
	{
		return ($this->relationship);
	}
	
	public function setRelationship($newRelationship)
	{
		$this->relationship = $newRelationship;
		$this->modified = true;
	}
	
}