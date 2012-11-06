<?php
//
// Description
// -----------
// This method will update the details for a project.  Any child items of the project
// must be updated using their modules update.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business to update the project for.
// project_id:		The ID of the project to update.
// name:			(optional) The new name for the project.
// category:		(optional) The new category for the project.
// assigned:		(optional) The new assigned list of users for the project.
// private:			(optional) The new setting for the private flag.
//					
//					yes - turn on privacy so project is only available to assigned users.
//					no - turn off privacy so anybody from the company can see the project.
//
// status:			(optional) The new status for the project.
//
//					1 - Open
//					60 - Completed
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_projects_update($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
        'project_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No project specified'), 
        'name'=>array('required'=>'no', 'blank'=>'no', 'errmsg'=>'No name specified'), 
        'category'=>array('required'=>'no', 'blank'=>'no', 'errmsg'=>'No category specified'), 
		'assigned'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'errmsg'=>'No assignments specified'),
		'private'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No private specified'),
		'status'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No status specified'),
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
    $rc = ciniki_projects_checkAccess($ciniki, $args['business_id'], 'ciniki.projects.update', $args['project_id']); 
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
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.projects');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Add the order to the database
	//
	$strsql = "UPDATE ciniki_projects SET last_updated = UTC_TIMESTAMP()";

	if( isset($args['private']) ) {
		$strsql .= ', perm_flags=(perm_flags&~0x01)';
	}

	//
	// Add all the fields to the change log
	//
	$changelog_fields = array(
		'category',
		'name',
		'status',
		);
	foreach($changelog_fields as $field) {
		if( isset($args[$field]) ) {
			$strsql .= ", $field = '" . ciniki_core_dbQuote($ciniki, $args[$field]) . "' ";
			$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.projects', 'ciniki_project_history', $args['business_id'], 
				2, 'ciniki_projects', $args['project_id'], $field, $args[$field]);
		}
	}
	$strsql .= "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['project_id']) . "' ";
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.projects');
	if( $rc['stat'] != 'ok' ) { 
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.projects');
		return $rc;
	}
	if( !isset($rc['num_affected_rows']) || $rc['num_affected_rows'] != 1 ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.projects');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'827', 'msg'=>'Unable to update task'));
	}


	//
	// Check if the assigned users has changed
	//
	if( isset($args['assigned']) && is_array($args['assigned']) ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'threadRemoveUserPerms');
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'threadAddUserPerms');
		//
		// Get the list of currently assigned users
		//
		$strsql = "SELECT user_id "
			. "FROM ciniki_project_users "
			. "WHERE project_id = '" . ciniki_core_dbQuote($ciniki, $args['project_id']) . "' "
			. "AND (perms&0x04) = 4 "
			. "";
		$rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.projects', 'users', 'user_id');
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'828', 'msg'=>'Unable to load task information', 'err'=>$rc['err']));
		}
		$task_users = $rc['users'];
		// 
		// Remove users no longer assigned
		//
		$to_be_removed = array_diff($task_users, $args['assigned']);
		if( is_array($to_be_removed) ) {
			foreach($to_be_removed as $user_id) {
				$rc = ciniki_core_threadRemoveUserPerms($ciniki, 'ciniki.projects', 'ciniki_project_users', 'project', $args['project_id'], $user_id, 0x04);
				if( $rc['stat'] != 'ok' ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'829', 'msg'=>'Unable to update task information', 'err'=>$rc['err']));
				}
			}
		}
		$to_be_added = array_diff($args['assigned'], $task_users);
		if( is_array($to_be_added) ) {
			foreach($to_be_added as $user_id) {
				$rc = ciniki_core_threadAddUserPerms($ciniki, 'ciniki.projects', 'ciniki_project_users', 'project', $args['project_id'], $user_id, (0x04));
				if( $rc['stat'] != 'ok' ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'830', 'msg'=>'Unable to update task information', 'err'=>$rc['err']));
				}
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

	return array('stat'=>'ok');
}
?>
