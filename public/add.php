<?php
//
// Description
// ===========
// This method will add a project to a business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business to add the project to.
// name:			The name of the project.
// category:		(optional) The category to for this project.
// assigned:		(optional) The ID's of the users who are assigned to this project.
// private:			(optional) Should the project be private to only assigned users.
//
//					yes - The project will only be shown in lists for those users assigned.
//					no - The project will be available to all users of a business.
//
// status:			(optional) The status of the project.
//			
//					0 - unknown
//					1 - Open project
//					60 - Completed project
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_projects_add($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
        'name'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No name specified'), 
        'category'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'errmsg'=>'No category specified'), 
		'assigned'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'errmsg'=>'No assignments specified'),
		'private'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'no', 'errmsg'=>'No private specified'),
		'status'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'1', 'errmsg'=>'No status specified'),
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
    $rc = ciniki_projects_checkAccess($ciniki, $args['business_id'], 'ciniki.projects.add', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//  
	// Turn off autocommit
	//  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.projects');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Setup flags
	//
	$perm_flags = 0;
	// Make messages private, always
	if( isset($args['private']) && $args['private'] == 'yes' ) {
		$perm_flags += 1;
	}
	//
	// Add the project to the database
	//
	$strsql = "INSERT INTO ciniki_projects (uuid, business_id, category, status, perm_flags, user_id, "
		. "name, "
		. "date_added, last_updated) VALUES ("
		. "UUID(), "
		. "'" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['category']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['status']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $perm_flags) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['name']) . "', "
		. "UTC_TIMESTAMP(), UTC_TIMESTAMP())"
		. "";
	$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.projects');
	if( $rc['stat'] != 'ok' ) { 
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.projects');
		return $rc;
	}
	if( !isset($rc['insert_id']) || $rc['insert_id'] < 1 ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.projects');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'822', 'msg'=>'Unable to add item'));
	}
	$project_id = $rc['insert_id'];

	//
	// Add all the fields to the change log
	//

	$changelog_fields = array(
		'category',
		'status',
		'perm_flags',
		'name',
		);
	foreach($changelog_fields as $field) {
		$insert_name = $field;
		if( isset($ciniki['request']['args'][$field]) && $ciniki['request']['args'][$field] != '' ) {
			$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.project', 'ciniki_project_history', $args['business_id'], 
				1, 'ciniki_projects', $project_id, $insert_name, $ciniki['request']['args'][$field]);
		}
	}

	//
	// Add the user who created the project, as a follower 
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'threadAddUserPerms');
	$rc = ciniki_core_threadAddUserPerms($ciniki, 'ciniki.projects', $args['business_id'], 'ciniki_project_users', 'project', $project_id, $ciniki['session']['user']['id'], (0x01|0x04));
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.projects');
		return $rc;
	}

	//
	// Add users who were assigned.  If the creator also is assigned the project, then they will be 
	// both a follower (above code) and assigned (below code).
	// Add the viewed flag to be set, so it's marked as unread for new assigned users.
	//
	if( isset($args['assigned']) && is_array($args['assigned']) ) {
		foreach( $args['assigned'] as $user_id ) {
			$rc = ciniki_core_threadAddUserPerms($ciniki, 'ciniki.projects', $args['business_id'], 'ciniki_project_users', 'project', $project_id, $user_id, (0x04));
			if( $rc['stat'] != 'ok' ) {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.projects');
				return $rc;
			}
		}
	}
	
	//
	// Commit the database changes
	//
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.projects');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Update the last_change date in the business modules
	// Ignore the result, as we don't want to stop user updates if this fails.
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
	ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'projects');

	//
	// FIXME: Notify users
	//

	return array('stat'=>'ok', 'id'=>$project_id);
}
?>
