<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_projects_objects($ciniki) {
    $objects = array();
    $objects['project'] = array(
        'name'=>'Projects',
        'table'=>'ciniki_projects',
        'fields'=>array(
            'category'=>array(),
            'status'=>array(),
            'perm_flags'=>array(),
            'user_id'=>array('ref'=>'ciniki.users.user'),
            'name'=>array(),
            ),
        'history_table'=>'ciniki_project_history',
        );
    $objects['user'] = array(
        'name'=>'Project User',
        'table'=>'ciniki_project_users',
        'fields'=>array(
            'project_id'=>array('ref'=>'ciniki.projects.project'),
            'user_id'=>array('ref'=>'ciniki.users.user'),
            'perms'=>array(),
            ),
        'history_table'=>'ciniki_project_history',
        );
    
    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
