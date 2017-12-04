<?php
//
// Description
// -----------
// This method will search the project names.  This is used to look for project
// names to link from other modules.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant to search for projects.
// start_needle:    The search string to search the project subjects for a match.
// limit:           The maximum number of results to return.
// 
// Returns
// -------
//
function ciniki_projects_searchNames($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'start_needle'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Search String'), 
        'limit'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Limit'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'projects', 'private', 'checkAccess');
    $rc = ciniki_projects_checkAccess($ciniki, $args['tnid'], 'ciniki.projects.searchNames'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Load the status maps for the text description of each status
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'projects', 'private', 'projectStatusMaps');
    $rc = ciniki_projects_projectStatusMaps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $status_maps = $rc['maps'];

    //
    // Search for the projects with a name that contains the start_needle
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    $strsql = "SELECT id, name, status, status AS status_text "
        . "FROM ciniki_projects "
        . "WHERE ciniki_projects.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND (name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . ") "
        . "ORDER BY status, name ";
    if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";   // is_numeric verified
    } else {
        $strsql .= "LIMIT 25 ";
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.projects', array(
        array('container'=>'projects', 'fname'=>'id', 'name'=>'project',
            'fields'=>array('id', 'name', 'status', 'status_text'),
            'maps'=>array('status_text'=>$status_maps)),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    return $rc;
}
?>
