<?php
//
// Description
// -----------
// This function returns the array of status text for ciniki_sapos_invoices.status.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_projects_projectStatusMaps($ciniki) {
	
	$status_maps = array(
		'10'=>'Open',
		'30'=>'Future',
		'40'=>'Dormant',
		'50'=>'Completed',
		'60'=>'Deleted',
		);
	
	return array('stat'=>'ok', 'maps'=>$status_maps);
}
?>
