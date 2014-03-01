<?php
//
// Description
// -----------
// This method will delete a project from the business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business the project is attached to.
// project_id:			The ID of the project to be removed.
//
// Returns
// -------
// <rsp stat="ok">
//
function ciniki_projects_projectDelete(&$ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'project_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Project'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access to business_id as owner
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'projects', 'private', 'checkAccess');
	$ac = ciniki_projects_checkAccess($ciniki, $args['business_id'], 'ciniki.projects.projectDelete');
	if( $ac['stat'] != 'ok' ) {
		return $ac;
	}

	//
	// Remove the project
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
	$rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.projects.project', 
		$args['project_id'], NULL, 0x07);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return array('stat'=>'ok');
}
?>
