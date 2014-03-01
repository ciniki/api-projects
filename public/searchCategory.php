<?php
//
// Description
// -----------
// Search the projects for category names.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business to search the projects.
// start_needle:	The search string to look for in the project categories.
// limit:			The maximum number of categories to return.
// 
// Returns
// -------
//
function ciniki_projects_searchCategory($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'start_needle'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Search String'), 
        'limit'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Limit'), 
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
    $rc = ciniki_projects_checkAccess($ciniki, $args['business_id'], 'ciniki.projects.searchCategory'); 
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
	// Get the number of faqs in each status for the business, 
	// if no rows found, then return empty array
	//
	$strsql = "SELECT category AS name "
		. "FROM ciniki_projects "
		. "WHERE ciniki_projects.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_projects.status < 60 "
		. "AND (category LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "AND category <> '' "
			. ") "
		. "";
	$strsql .= "ORDER BY category "
		. "";
	if( isset($args['limit']) && $args['limit'] != '' && $args['limit'] > 0 ) {
		$strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
	} else {
		$strsql .= "LIMIT 25 ";
	}
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.projects', array(
		array('container'=>'categories', 'fname'=>'name', 'name'=>'category', 
			'fields'=>array('name')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['categories']) ) {
		return array('stat'=>'ok', 'categories'=>array());
	}
	return array('stat'=>'ok', 'categories'=>$rc['categories']);
}
?>
