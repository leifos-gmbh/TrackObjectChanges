<#1>
<?php

$ilDB->createTable('evnt_evhk_tobc_events', array(
	
	'evt_id'			=> array(
							'type'		=> 'integer',
							'length'	=> 4,
							'notnull'	=> true,
							'default'	=> 0
	),
	
	'evt_obj_id'		=> array(
							'type'		=> 'integer',
							'length'	=> 4,
							'notnull'	=> true,
							'default'	=> 0
	),
	
    'evt_obj_type'		=> array(
							'type'		=> 'text',
							'length'	=> 4,
							'notnull'	=> true
	),
	
	'evt_event_type'	=> array(
							'type'		=> 'text',
							'length'	=> 16,
							'notnull'	=> true
	),
	
	'evt_event_date'	=> array(
							'type'		=> 'timestamp',
							'notnull'	=> true
	)
	
));

$ilDB->addPrimaryKey('evnt_evhk_tobc_events', array('evt_id'));

$ilDB->createSequence('evnt_evhk_tobc_events');

// @todo: create required indexes

?>
