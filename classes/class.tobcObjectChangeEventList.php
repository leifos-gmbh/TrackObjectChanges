<?php

/**
 * Class to manage multiple object change events
 *
 * @author		Björn Heyser <bheyser@databay.de>
 * @version		$Id: class.tobcObjectChangeEventList.php 32552 2012-01-06 14:02:15Z smeyer $
 *
 * @package		<PLUGINS>/Services/EventHandling/EventHook/TrackObjectChanges
 */
class tobcObjectChangeEventList implements Iterator
{
	private $position = 0;
	
	private $events = array();
	
	private function __construct()
	{
	}
	
	/**
	 * @param tobcObjectChangeEvent $objectChangeEvent 
	 */
	public function addEvent(tobcObjectChangeEvent $objectChangeEvent)
	{
		$this->events[] = $objectChangeEvent;
	}
	
	/**
	 * @return array 
	 */
	public function getEvents()
	{
		return $this->events;
	}
	
	public static function checkObjectTypeFilter($objectTypeFilter)
	{
		if( !is_array($objectTypeFilter) || !count($objectTypeFilter) )
		{
			throw new ilException('object type filter must be a non empty array of tracked object types!');
		}
		
		if( count(array_diff($objectTypeFilter, tobcObjectChangeEvent::getTrackedObjectTypes())) )
		{
			throw new ilException('object type filter must contain tracked object types only!');
		}
	}

	public static function checkEventTypeFilter($eventTypeFilter)
	{
		if( !is_array($eventTypeFilter) || !count($eventTypeFilter) )
		{
			throw new ilException('event type filter must be a non empty array of valid event types!');
		}
		
		if( count(array_diff($eventTypeFilter, tobcObjectChangeEvent::getValidEventTypes())) )
		{
			throw new ilException('event type filter must contain valid event types only!');
		}
	}

	/**
	 * @global ilDB $ilDB
	 * @param array $objectTypes
	 * @param array $eventTypes
	 * @return tobcObjectChangeEventList $objectChangeEventList 
	 */
	public static function getListByObjTypesAndEventTypes($objectTypes, $eventTypes)
	{
		self::checkObjectTypeFilter($objectTypes);
		self::checkEventTypeFilter($eventTypes);
		
		global $ilDB;
		
		$evtObjType_IN_objectTypes = $ilDB->in('evt_obj_type', $objectTypes, false, 'text');
		$evtEventType_IN_eventTypes = $ilDB->in('evt_event_type', $eventTypes, false, 'text');
		
		$queryString = "
			SELECT		evt_id			id,
			
						evt_obj_id		obj_id,
						evt_obj_type	obj_type,
						
						evt_event_type	evt_type,
						evt_event_date	evt_date
						
			FROM		evnt_evhk_tobc_events
			
			WHERE		$evtObjType_IN_objectTypes
			AND			$evtEventType_IN_eventTypes
		
			ORDER BY	obj_id		ASC,
						evt_date	ASC
		";
		
		$resultSet = $ilDB->query($queryString);
		
		$objectChangeEventList = new self();
		while( $dataSet = $ilDB->fetchAssoc($resultSet) )
		{
			$objectChangeEvent = new tobcObjectChangeEvent();
			$objectChangeEvent->assign($dataSet);
			
			$objectChangeEventList->addEvent($objectChangeEvent);
		}
		
		return $objectChangeEventList;
	}
	
	/**
	 * @param array $objectTypes
	 * @return tobcObjectChangeEventList $objectChangeEventList 
	 */
	public static function getListByObjTypes($objectTypes)
	{
		$eventTypes = tobcObjectChangeEvent::getValidEventTypes();
		
		$objectChangeEventList = self::getListByObjTypesAndEventTypes($objectTypes, $eventTypes);
		
		return $objectChangeEventList;
	}
	
	/**
	 * @global ilDB $ilDB
	 * @param integer $objId
	 * @param array $eventTypes
	 * @return boolean $eventsExist
	 */
	public static function eventsExistByObjIdAndEventTypes($objId, $eventTypes)
	{
		self::checkEventTypeFilter($eventTypes);
		
		global $ilDB;
		
		$objType_IN_eventTypes = $ilDB->in('evt_event_type', $eventTypes, false, 'text');
		
		$queryString = "
			SELECT		COUNT(evt_id)	num_events
						
			FROM		evnt_evhk_tobc_events
			
			WHERE		evt_obj_id = %s
			AND			$objType_IN_eventTypes
		";
		
		$resultSet = $ilDB->queryF(
			$queryString, array('integer'), array($objId)
		);
		
		$dataSet = $ilDB->fetchAssoc($resultSet);

		$eventsExist = (bool) $dataSet['num_events'];
		
		return $eventsExist;
	}
	
	/**
	 *
	 * @global ilDB $ilDB
	 * @param integer $objId
	 * @param array $eventTypes 
	 */
	public static function deleteEventsByObjIdAndEventTypes($objId, $eventTypes)
	{
		self::checkEventTypeFilter($eventTypes);
		
		global $ilDB;
		
		$objType_IN_eventTypes = $ilDB->in('evt_event_type', $eventTypes, false, 'text');
		
		$queryString = "
			DELETE FROM		evnt_evhk_tobc_events
			
			WHERE			evt_obj_id = %s
			AND				$objType_IN_eventTypes
		";
	
		$ilDB->manipulateF(
			$queryString, array('integer'), array($objId)
		);
	}
	
	/**
	 * @param integer $objId 
	 */
	public static function deleteEventsByObjId($objId)
	{
		self::deleteEventsByObjIdAndEventTypes(
				$objId, tobcObjectChangeEvent::getValidEventTypes()
		);
	}
	
	/**
	 * @param string $eventType
	 * @param integer $objId
	 * @param string $objType 
	 */
	public static function saveEvent($eventType, $objId, $objType)
	{
		$eventDate = date('Y-m-d H:i:s');
		
		$changeEvent = new tobcObjectChangeEvent();

		$changeEvent->setObjId($objId);
		$changeEvent->setObjType($objType);
		
		$changeEvent->setEventType($eventType);
		$changeEvent->setEventDate($eventDate);
		
		$changeEvent->save();
	}
	
	/**
	 * @return ilObjectChangeEvent $domain
	 */
	public function current()
	{
		return $this->events[$this->position];
	}
	
	/**
	 * @return integer $position
	 */
	public function key()
	{
		return $this->position;
	}	
	
	public function next()
	{
		++$this->position;
	}
	
	public function rewind()
	{
		$this->position = 0;
	}
	
	/**
	 * @return boolean $valid
	 */
	public function valid()
	{
		if( isset($this->events[$this->position]) )
		{
			return true;
		}
		
    	return false;
	}
}


