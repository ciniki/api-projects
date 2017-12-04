<?php
//
// Description
// -----------
// This method will delete a project from the tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the project is attached to.
// project_id:          The ID of the project to be removed.
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
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'project_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Project'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'projects', 'private', 'checkAccess');
    $ac = ciniki_projects_checkAccess($ciniki, $args['tnid'], 'ciniki.projects.projectDelete');
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }

    //
    // Remove the project
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.projects.project', 
        $args['project_id'], NULL, 0x07);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    return array('stat'=>'ok');
}
?>
