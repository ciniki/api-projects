<?php
//
// Description
// -----------
// This function will clean up the history for projects.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_projects_dbIntegrityCheck($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'fix'=>array('required'=>'no', 'default'=>'no', 'name'=>'Fix Problems'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to business_id as owner, or sys admin
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'projects', 'private', 'checkAccess');
    $rc = ciniki_projects_checkAccess($ciniki, $args['business_id'], 'ciniki.projects.dbIntegrityCheck');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbFixTableHistory');

    if( $args['fix'] == 'yes' ) {
        //
        // Update the history for ciniki_projects
        //
        $rc = ciniki_core_dbFixTableHistory($ciniki, 'ciniki.projects', $args['business_id'],
            'ciniki_projects', 'ciniki_project_history', 
            array('uuid', 'category', 'status', 'perm_flags', 'user_id', 'name'));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }

        //
        // Update the history for ciniki_project_users
        //
        $rc = ciniki_core_dbFixTableHistory($ciniki, 'ciniki.projects', $args['business_id'],
            'ciniki_project_users', 'ciniki_project_history', 
            array('uuid', 'project_id', 'user_id', 'perms'));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }

        //
        // Check for items missing a UUID
        //
        $strsql = "UPDATE ciniki_project_history SET uuid = UUID() WHERE uuid = ''";
        $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.projects');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }

        //
        // Remote any entries with blank table_key, they are useless we don't know what they were attached to
        //
        $strsql = "DELETE FROM ciniki_project_history WHERE table_key = ''";
        $rc = ciniki_core_dbDelete($ciniki, $strsql, 'ciniki.projects');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
    }

    return array('stat'=>'ok');
}
?>
