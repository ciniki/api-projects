<?php
//
// Description
// ===========
// This method will return all the details for a project, and
// the children if a project.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business to get the ATDO for.
// project_id:		The ID of the project to get.
// children:		(optional) The children flag to specify returning all child projects if specified as yes.
// 
// Returns
// -------
// <project name="Project Name">
//		<appointments>
//			<appointment id="56" subject="Project appointment"/>
//		</appointments>
//		<tasks>
//			<task id="28" subject="Project task" />
//		</tasks>
//		<messages>
//			<message id="92" subject="Project message" />
//		</messages>
//		<notes>
//			<note id="73" subject="Project note" />
//		</notes>
//		<files>
//			<file id="23" name="Project file" />
//		</files>
// </project>
//
function ciniki_projects_get($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
        'project_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No project specified'), 
		'children'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'no', 'errmsg'=>'No children flag specified'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'projects', 'private', 'checkAccess');
    $rc = ciniki_projects_checkAccess($ciniki, $args['business_id'], 'ciniki.projects.get'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
	$modules = $rc['modules'];

	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timezoneOffset');
	$utc_offset = ciniki_users_timezoneOffset($ciniki);

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$datetime_format = ciniki_users_datetimeFormat($ciniki);
	$date_format = ciniki_users_dateFormat($ciniki);

	//
	// Get the project information
	//
	$strsql = "SELECT ciniki_projects.id, ciniki_projects.name, user_id, "
		. "IF((ciniki_projects.perm_flags&0x01)=1, 'yes', 'no') AS private, "
		. "ciniki_projects.status, ciniki_projects.category, "
		. "DATE_FORMAT(CONVERT_TZ(ciniki_projects.date_added, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS date_added, "
		. "DATE_FORMAT(CONVERT_TZ(ciniki_projects.last_updated, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS last_updated "
		. "FROM ciniki_projects "
		. "WHERE ciniki_projects.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_projects.id = '" . ciniki_core_dbQuote($ciniki, $args['project_id']) . "' "
		. "";
	
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.projects', 'project');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['project']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'823', 'msg'=>'Unable to find item'));
	}
	$project = $rc['project'];

	$project['followers'] = array();
	$project['assigned'] = '';
	$project['viewed'] = '';

	$user_ids = array($rc['project']['user_id']);

	//
	// Get the list of users attached to the project
	//
	$strsql = "SELECT project_id, user_id, perms "
		. "FROM ciniki_project_users "
		. "WHERE project_id = '" . ciniki_core_dbQuote($ciniki, $args['project_id']) . "' ";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbRspQueryPlusUserIDs');
	$rc = ciniki_core_dbRspQueryPlusUserIDs($ciniki, $strsql, 'ciniki.projects', 'users', 'user', array('stat'=>'ok', 'users'=>array(), 'user_ids'=>array()));
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'824', 'msg'=>'Unable to load item information', 'err'=>$rc['err']));
	}
	$project_users = $rc['users'];
	$user_ids = array_merge($user_ids, $rc['user_ids']);

	//
	// Get the users which are linked to these accounts
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'userListByID');
	$rc = ciniki_users_userListByID($ciniki, 'users', $user_ids, 'display_name');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'825', 'msg'=>'Unable to load item information', 'err'=>$rc['err']));
	}
	if( !isset($rc['users']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'826', 'msg'=>'Unable to load item information', 'err'=>$rc['err']));
	}
	$users = $rc['users'];

	//
	// Build the list of followers and users assigned to the project
	//
	foreach($project_users as $unum => $user) {
		$display_name = 'unknown';
		if( isset($users[$user['user']['user_id']]) ) {
			$display_name = $users[$user['user']['user_id']]['display_name'];
		}
		// Followers
		if( ($user['user']['perms'] & 0x01) > 0 ) {
			array_push($project['followers'], array('user'=>array('id'=>$user['user']['user_id'], 'display_name'=>$display_name)));
		}
		// User has viewed the project
		if( ($user['user']['perms'] & 0x08) > 0 ) {
			if( $project['viewed'] != '' ) {
				$project['viewed'] .= ',';
			}
			$project['viewed'] .= $user['user']['user_id'];
		}
//		// User has deleted the project
//		if( ($user['user']['perms'] & 0x10) > 0 ) {
//			if( $project['deleted'] != '' ) {
//				$project['deleted'] .= ',';
//			}
//			$project['deleted'] .= $user['user']['user_id'];
//		}
		// Assigned to
		if( ($user['user']['perms'] & 0x04) > 0 ) {
			if( $project['assigned'] != '' ) {
				$project['assigned'] .= ',';
			}
			$project['assigned'] .= $user['user']['user_id'];
		}
	}

	//
	// Fill in the project information with user info
	//
	if( isset($project['user_id']) && isset($users[$project['user_id']]) ) {
		$project['user_display_name'] = $users[$project['user_id']]['display_name'];
	}

	//
	// Check if the children should be loaded for a project
	//
	if( isset($args['children']) && $args['children'] == 'yes' ) {
		if( isset($modules['ciniki.atdo']) ) {
			$project['appointments'] = array();
			$project['tasks'] = array();
			$project['documents'] = array();
			$project['notes'] = array();
			$project['messages'] = array();
			ciniki_core_loadMethod($ciniki, 'ciniki', 'atdo', 'private', 'projectChildren');
			$rc = ciniki_atdo_projectChildren($ciniki, $args['business_id'], $args['project_id'], 'open'); 
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			if( isset($rc['project']) ) {
				$project = array_merge($project, $rc['project']);
			}
//			if( isset($rc['appointments']) ) {
//				$project['appointments'] = $rc['appointments'];
//			}
//			if( isset($rc['tasks']) ) {
//				$project['tasks'] = $rc['tasks'];
//			}
//			if( isset($rc['documents']) ) {
//				$project['documents'] = $rc['documents'];
//			}
//			if( isset($rc['notes']) ) {
//				$project['notes'] = $rc['notes'];
//			}
//			if( isset($rc['messages']) ) {
//				$project['messages/'] = $rc['messages/'];
//			}
		}
		if( isset($modules['ciniki.filedepot']) ) {
			$project['files'] = array();
			ciniki_core_loadMethod($ciniki, 'ciniki', 'filedepot', 'private', 'projectChildren');
			$rc = ciniki_filedepot_projectChildren($ciniki, $args['business_id'], $args['project_id']);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			if( isset($rc['project']) ) {
				$project = array_merge($project, $rc['project']);
			}
		}
	}

	return array('stat'=>'ok', 'project'=>$project);
}
?>
