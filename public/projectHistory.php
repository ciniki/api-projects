<?php
//
// Description
// -----------
// This method will return the history for an element of a project. 
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to get the history for.
// project_id:			The ID of the project to get the history for.
// field:				The database field to get the history for.
//
// Returns
// -------
//
function ciniki_projects_projectHistory($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'project_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Project'), 
		'field'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Field'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access to business_id as owner, or sys admin
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'projects', 'private', 'checkAccess');
	$rc = ciniki_projects_checkAccess($ciniki, $args['business_id'], 'ciniki.projects.projectHistory');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	if( $args['field'] == 'appointment_date' ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistoryReformat');
		return ciniki_core_dbGetModuleHistoryReformat($ciniki, 'ciniki.projects', 'ciniki_project_history', $args['business_id'], 'ciniki_projects', $args['project_id'], $args['field'], 'datetime');
	}
	if( $args['field'] == 'due_date' ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistoryReformat');
		return ciniki_core_dbGetModuleHistoryReformat($ciniki, 'ciniki.projects', 'ciniki_project_history', $args['business_id'], 'ciniki_projects', $args['project_id'], $args['field'], 'datetime');
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
	return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.projects', 'ciniki_project_history', $args['business_id'], 'ciniki_projects', $args['project_id'], $args['field']);
}
?>
