<?php
//
// Description
// ===========
// This method will add a project to a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant to add the project to.
// name:            The name of the project.
// category:        (optional) The category to for this project.
// assigned:        (optional) The ID's of the users who are assigned to this project.
// private:         (optional) Should the project be private to only assigned users.
//
//                  yes - The project will only be shown in lists for those users assigned.
//                  no - The project will be available to all users of a tenant.
//
// status:          (optional) The status of the project.
//          
//                  0 - unknown
//                  1 - Open project
//                  60 - Completed project
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_projects_projectAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'name'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Name'), 
        'category'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Category'), 
        'assigned'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'name'=>'Assigned'),
//      'private'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'no', 'validlist'=>array('no', 'yes'), 'name'=>'Private'),
        'perm_flags'=>array('required'=>'no', 'blank'=>'no', 'default'=>'0', 'name'=>'Permissions'),
        'status'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'1', 'name'=>'Status'),
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
    $rc = ciniki_projects_checkAccess($ciniki, $args['tnid'], 'ciniki.projects.projectAdd'); 
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

//  if( $args['private'] == 'yes' ) {
//      $args['perm_flags'] = 0x01;
//  } else {
//      $args['perm_flags'] = 0;
//  }
    $args['user_id'] = $ciniki['session']['user']['id'];

    //
    // Add the project
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.projects.project', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.projects');
        return $rc;
    }
    $project_id = $rc['id'];

    //
    // Add the user who created the project, as a follower 
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'threadAddUserPerms');
    $rc = ciniki_core_threadAddUserPerms($ciniki, 'ciniki.projects', 'user', $args['tnid'], 
        'ciniki_project_users', 'ciniki_project_history', 
        'project', $project_id, $ciniki['session']['user']['id'], (0x01|0x04));
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
            $rc = ciniki_core_threadAddUserPerms($ciniki, 'ciniki.projects', 'user', 
                $args['tnid'], 'ciniki_project_users', 'ciniki_project_history',
                'project', $project_id, $user_id, (0x04));
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
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'projects');

    //
    // FIXME: Notify users
    //

    return array('stat'=>'ok', 'id'=>$project_id);
}
?>
