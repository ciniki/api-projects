<?php
//
// Description
// ===========
// This method will return a list of projects, organized by category.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant to get the projects for.
// status:          Return only projects in this status.
// limit:           The maximum number of projects to return.
// 
// Returns
// -------
// <projects>
//      <project id="1" name="project name" assigned="yes" private="yes" />
// </projects>
//
function ciniki_projects_projectList($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'status'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Status'), 
        'limit'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Limit'), 
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
    $rc = ciniki_projects_checkAccess($ciniki, $args['tnid'], 'ciniki.projects.projectList'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);

    //
    // Load the status maps for the text description of each status
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'projects', 'private', 'projectStatusMaps');
    $rc = ciniki_projects_projectStatusMaps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $status_maps = $rc['maps'];

    $strsql = "SELECT ciniki_projects.id, "
        . "IF(category='', 'Uncategorized', category) AS category, "
        . "name, "
        . "IF((ciniki_projects.perm_flags&0x01)=1, 'yes', 'no') AS private, "
        . "ciniki_projects.status, ciniki_projects.status AS status_text, "
        . "IF((u1.perms&0x04)=4, 'yes', 'no') AS assigned, "
        . "u2.user_id AS assigned_user_ids, "
        . "IFNULL(u3.display_name, '') AS assigned_users "
        . "FROM ciniki_projects "
        . "LEFT JOIN ciniki_project_users AS u1 ON (ciniki_projects.id = u1.project_id AND u1.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "') "
        . "LEFT JOIN ciniki_project_users AS u2 ON (ciniki_projects.id = u2.project_id && (u2.perms&0x04) = 4) "
        . "LEFT JOIN ciniki_users AS u3 ON (u2.user_id = u3.id) "
        . "WHERE ciniki_projects.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    if( isset($args['status']) && $args['status'] > 0 ) {
        $strsql .= "AND ciniki_projects.status = '" . ciniki_core_dbQuote($ciniki, $args['status']) . "' ";
//      switch($args['status']) {
//          case 'Open':
//          case 'open': $strsql .= "AND ciniki_projects.status = 10 ";
//              break;
//          case 'Closed':
//          case 'closed': $strsql .= "AND ciniki_projects.status = 60 ";
//              break;
//      }
    }
    // Check for public/private notes, and if private make sure user created or is assigned
    $strsql .= "AND ((ciniki_projects.perm_flags&0x01) = 0 "  // Public to tenant
            // created by the user requesting the list
            . "OR ((ciniki_projects.perm_flags&0x01) = 1 AND ciniki_projects.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "') "
            // Assigned to the user requesting the list
            . "OR ((ciniki_projects.perm_flags&0x01) = 1 AND (u1.perms&0x04) = 0x04) "
            . ") "
        . "ORDER BY category, assigned DESC, ciniki_projects.id, u3.display_name "
        . "";
    if( isset($args['limit']) && $args['limit'] != '' && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.projects', array(
        array('container'=>'categories', 'fname'=>'category', 'name'=>'category',
            'fields'=>array('name'=>'category')),
        array('container'=>'projects', 'fname'=>'id', 'name'=>'project',
            'fields'=>array('id', 'name', 'status', 'status_text',
                'private', 'assigned', 'assigned_user_ids', 'assigned_users'), 
            'idlists'=>array('assigned_user_ids'), 'lists'=>array('assigned_users'),
            'maps'=>array('status_text'=>$status_maps)),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    return $rc;
}
?>
