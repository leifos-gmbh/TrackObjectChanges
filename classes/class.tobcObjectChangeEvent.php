<?php

/**
 * Class to manage one object change event
 *
 * @author		Björn Heyser <bheyser@databay.de>
 * @version		$Id: class.tobcObjectChangeEvent.php 38620 2012-12-03 15:01:19Z smeyer $
 *
 * @package		<PLUGINS>/Services/EventHandling/EventHook/TrackObjectChanges
 */
class tobcObjectChangeEvent
{
	private static $trackedObjectTypes = array('cat', 'htlm', 'file');
	private static $allowedContainerTypes = array('cat');
	
	const EVENT_TYPE_CREATE  = 'CREATE';
	const EVENT_TYPE_UPDATE  = 'UPDATE';
	const EVENT_TYPE_REMOVE  = 'REMOVE';
	const EVENT_TYPE_TOTRASH = 'TOTRASH';
	const EVENT_TYPE_RESTORE = 'RESTORE';
	
	private $id = null;
	
	private $objId = null;
	
	private $objType = null;
	
	private $eventType = null;
	
	private $eventDate = null;
	
	public function __construct($id = null)
	{
		if( !is_null($id) )
		{
			$this->setId($id);
			$this->read();
		}
	}

	public function read()
	{
		if( !$this->getId() )
		{
			throw new ilException('Cannot read object change event without valid id!');
		}
		
		global $ilDB;
		
		$query = "
			SELECT		evt_id			id,
			
						evt_obj_id		obj_id,
						evt_obj_type	obj_type,
						
						evt_event_type	evt_type,
						evt_event_date	evt_date
						
			FROM		evnt_evhk_tobc_events
			
			WHERE		evt_id = %s
		";
		
		$resultSet = $ilDB->queryF(
			$query, array('integer'), array($this->getId())
		);
		
		$dataSet = $ilDB->fetchAssoc($resultSet);
				
		if($dataSet)
		{
			$this->assign($dataSet);
			
			return true;
		}
		
		throw new ilException(
			'Could not find dataset with evt_id '.$this->getId().' in database table evnt_evhk_tobc_events'
		);
	}
	
	public function assign($dataSet)
	{
		$this->setId($dataSet['id']);
		
		$this->setObjId($dataSet['obj_id']);
		$this->setObjType($dataSet['obj_type']);
		
		$this->setEventType($dataSet['evt_type']);
		$this->setEventDate($dataSet['evt_date']);
		
		return $this;
	}
	
	public function save()
	{
		if( $this->getId() )
		{
			$success = $this->update();
		}
		else
		{
			$success = $this->insert();
		}
		
		return $success;
	}
	
	private function insert()
	{
		global $ilDB;
		
		$nextId = $ilDB->nextId('evnt_evhk_tobc_events');
		
		$ilDB->insert('evnt_evhk_tobc_events', array(
			'evt_id'			=> array('integer', $nextId),
			'evt_obj_id'		=> array('integer', $this->getObjId()),
			'evt_obj_type'		=> array('text', $this->getObjType()),
			'evt_event_type'	=> array('text', $this->getEventType()),
			'evt_event_date'	=> array('timestamp', $this->getEventDate())
		));
	}
	
	private function update()
	{
		global $ilDB;
		
		$ilDB->update(
			'evnt_evhk_tobc_events',
			array(
				'evt_obj_id'		=> array('integer', $this->getObjId()),
				'evt_obj_type'		=> array('text', $this->getObjType()),
				'evt_event_type'	=> array('text', $this->getEventType()),
				'evt_event_date'	=> array('timestamp', $this->getEventDate())
			),
			array(
				'evt_id'			=> array('integer', $this->getId()),
			)
		);
	}
	
	public function delete()
	{
		if( !$this->getId() )
		{
			throw new ilException('Cannot delete object change event without valid id!');
		}
		
		global $ilDB;
		
		$query = "
			DELETE FROM		evnt_evhk_tobc_events

			WHERE			evt_id = %s
		";
		
		$affectedRows = $ilDB->manipulateF(
			$query, array('integer'), array($this->getId())
		);
		
		if( $affectedRows < 1 )
		{
			/*
			throw new ilException(
				'Could not delete dataset with evt_id '.$this->getId().' '.
				'in database table evnt_evhk_tobc_events'
			);
			*/
		}
		elseif( $affectedRows > 1 )
		{
			throw new ilException(
				'More than one dataset were deleted for evt_id '.$this->getId().' '.
				'in database table evnt_evhk_tobc_events'
			);
		}
		
		return true;
	}
	
	public function getId()
	{
		return $this->id;
	}

	public function setId($id)
	{
		$this->id = (int)$id;
		return $this;
	}

	public function getObjId()
	{
		return $this->objId;
	}

	public function setObjId($objId)
	{
		$this->objId = (int)$objId;
		return $this;
	}

	public function getObjType()
	{
		return $this->objType;
	}

	public function setObjType($objType)
	{
		$this->objType = $objType;
		return $this;
	}

	public function getEventType()
	{
		return $this->eventType;
	}

	public function setEventType($eventType)
	{
		$this->eventType = $eventType;
		return $this;
	}

	public function getEventDate()
	{
		return $this->eventDate;
	}

	public function setEventDate($eventDate)
	{
		$this->eventDate = $eventDate;
		return $this;
	}
	
	public static function getTrackedObjectTypes()
	{
		return self::$trackedObjectTypes;
	}
	
	public static function getAllowedContainerTypes()
	{
		return self::$allowedContainerTypes;
	}
	
	public static function getValidEventTypes()
	{
		$validEventTypes = array(
			self::EVENT_TYPE_CREATE,
			self::EVENT_TYPE_UPDATE,
			self::EVENT_TYPE_REMOVE,
			self::EVENT_TYPE_TOTRASH,
			self::EVENT_TYPE_RESTORE
		);
		
		return $validEventTypes;
	}
}

