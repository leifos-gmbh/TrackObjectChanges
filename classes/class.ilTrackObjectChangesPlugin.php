<?php

include_once("./Services/EventHandling/classes/class.ilEventHookPlugin.php");

/**
 * Class for plugin TrackObjectChanges docking to plugin slot Services/EventHandling/EventHook
 *
 * @author		Björn Heyser <bheyser@databay.de>
 * @version		$Id: class.ilTrackObjectChangesPlugin.php 35301 2012-06-29 14:14:34Z smeyer $
 *
 * @package		<PLUGINS>/Services/EventHandling/EventHook/TrackObjectChanges
 */
class ilTrackObjectChangesPlugin extends ilEventHookPlugin
{	
	final public function getPluginName()
	{
		return "TrackObjectChanges";
	}
	
	protected function init()
	{
		$this->includeClass('class.tobcObjectChangeEvent.php');
	}

	public function handleEvent($a_component, $a_event, $a_params)
	{
		
		$GLOBALS['ilLog']->write(__METHOD__.': '.$a_component);
		
		self::debEvents($a_component, $a_event, $a_params);
		
		$objId = $a_params['obj_id'];
		
		if( isset($a_params['obj_type']) )
		{
			$objType = $a_params['obj_type'];
		}
		else
		{
			$objType = ilObject::_lookupType($a_params['obj_id']);
		}
		
		if( !in_array($objType, tobcObjectChangeEvent::getTrackedObjectTypes()) )
		{
			return true;
		}
		
		$this->includeClass('class.tobcObjectChangeEvent.php');
		$this->includeClass('class.tobcObjectChangeEventList.php');
		
		$eventType = null;
		
		switch($a_component)
		{
			case 'Services/Object':
				
				switch($a_event)
				{
					case 'create':
						
						$this->handleCreateEvent($objId, $objType); break;
						
					case 'update':
						
						$this->handleUpdateEvent($objId, $objType); break;
						
					case 'delete':
						
						$this->handleRemoveEvent($objId, $objType); break;
					
					case 'toTrash':
						
						$this->handleToTrashEvent($objId, $objType); break;
						
					case 'undelete':
						
						$this->handleRestoreEvent($objId, $objType); break;

					case 'cut':

						$this->handleUpdateEvent($objId, $objType); break;

					case 'link':

						$this->handleUpdateEvent($objId, $objType); break;
				}
			
			case 'Services/Container':
				
				if( $a_event == 'saveSorting' )
				{
					$this->handleUpdateEvent($objId, $objType); break;
				}
				if( $a_event == 'cut' )
				{
					$this->handleUpdateEvent($objId, $objType); break;
				}
				if( $a_event == 'link' )
				{
					$this->handleUpdateEvent($objId, $objType); break;
				}

			case 'Modules/Category':
			
				if( $a_event == 'update' )
				{
					$this->handleUpdateEvent($objId, $objType); break;
				}
				
			case 'Services/FileSystemStorage':
				
				$handledEvents = array(
					'createDirectory',
					'deleteFile',
					'renameFile',
					'unzipFile',
					'uploadFile'
				);
				
				if( in_array($a_event, $handledEvents) )
				{
					$this->handleUpdateEvent($objId, $objType); break;
				}

			case 'Modules/HTMLLearningModule':
			
				$handledEvents = array(
					'addBibItem',
					'deleteBibItem',
					'saveBibItem'
				);
				
				if( in_array($a_event, $handledEvents) )
				{
					$this->handleUpdateEvent($objId, $objType); break;
				}
				
			case 'Services/MetaData':
				
				$handledEvents = array(
					'updateQuickEdit',
					'updateGeneral',
					'updateTechnical',
					'updateLifecycle',
					'updateMetaMetaData',
					'updateRights',
					'updateEducational',
					'updateRelation',
					'updateAnnotation',
					'updateClassification',
					'deleteElement',
					'addSection',
					'deleteSection',
					'addSectionElement'
				);
				
				if( in_array($a_event, $handledEvents) )
				{
					$this->handleUpdateEvent($objId, $objType); break;
				}
		}
		
		return true;
	}
	
	/**
	 * der neue create event wird in jedem fall in der queue gespeichert.
	 * 
	 * @param integer $objId
	 * @param string $objType
	 */
	private function handleCreateEvent($objId, $objType)
	{
		tobcObjectChangeEventList::saveEvent(
				tobcObjectChangeEvent::EVENT_TYPE_CREATE, $objId, $objType
		);
	}
	
	/**
	 * der neue update event wird nur in der queue gespeichert,
	 * wenn noch KEIN update oder create event in der queue steht.
	 * 
	 * @param integer $objId
	 * @param string $objType
	 */
	private function handleUpdateEvent($objId, $objType)
	{
		$eventTypes = array(
			tobcObjectChangeEvent::EVENT_TYPE_CREATE,
			tobcObjectChangeEvent::EVENT_TYPE_UPDATE
		);
		
		if( ! tobcObjectChangeEventList::eventsExistByObjIdAndEventTypes($objId, $eventTypes) )
		{
			tobcObjectChangeEventList::saveEvent(
					tobcObjectChangeEvent::EVENT_TYPE_UPDATE, $objId, $objType
			);
		}
	}
	
	/**
	 * befindet sich in der queue ein create event,
	 * wird der neue remove event NICHT in der queue gespeichert.
	 * 
	 * befindet sich in der queue KEIN create event,
	 * wird der neue remove event in der queue gespeichert.
	 * 
	 * in jedem fall werden die vorherigen events aus der queue gelöscht.
	 * 
	 * @param integer $objId
	 * @param string $objType
	 */
	private function handleRemoveEvent($objId, $objType)
	{
		$eventTypes = array(
			tobcObjectChangeEvent::EVENT_TYPE_CREATE
		);
		
		$saveNewEvent = false;
		
		if( ! tobcObjectChangeEventList::eventsExistByObjIdAndEventTypes($objId, $eventTypes) )
		{
			$saveNewEvent = true;
		}
		
		tobcObjectChangeEventList::deleteEventsByObjId($objId);

		if( $saveNewEvent )
		{
			tobcObjectChangeEventList::saveEvent(
					tobcObjectChangeEvent::EVENT_TYPE_REMOVE, $objId, $objType
			);
		}
	}
	
	/**
	 * der neue totrash event wird in jedem fall in der queue gespeichert.
	 * 
	 * @param integer $objId
	 * @param string $objType
	 */
	private function handleToTrashEvent($objId, $objType)
	{
		tobcObjectChangeEventList::saveEvent(
				tobcObjectChangeEvent::EVENT_TYPE_TOTRASH, $objId, $objType
		);
	}
	
	/**
	 * befindet sich in der queue bereits ein totrash event,
	 * wird dieser gelöscht und der neue restore event wird nicht gespeichert.
	 * 
	 * befindet sich in der queue kein vorheriger totrash event,
	 * wird der neue restore event in der queue gespeichert.
	 * 
	 * @param integer $objId
	 * @param string $objType
	 */
	private function handleRestoreEvent($objId, $objType)
	{
		$eventTypes = array(
			tobcObjectChangeEvent::EVENT_TYPE_TOTRASH
		);
		
		if( ! tobcObjectChangeEventList::eventsExistByObjIdAndEventTypes($objId, $eventTypes) )
		{
			tobcObjectChangeEventList::saveEvent(
					tobcObjectChangeEvent::EVENT_TYPE_RESTORE, $objId, $objType
			);
		}
		else
		{
			tobcObjectChangeEventList::deleteEventsByObjIdAndEventTypes(
					$objId, array(tobcObjectChangeEvent::EVENT_TYPE_TOTRASH)
			);
		}
	}
		
	private static function debEvents($component, $event, $params)
	{
		static $requestEvents = null;
		
		if( is_null($requestEvents) )
		{
			$requestEvents = array();
			
			$rootUser = ilObjectFactory::getInstanceByObjId(SYSTEM_USER_ID);
			
			$debEventsShutdownFunction = create_function(
				'$subject, $events', 'mail("'.$rootUser->getEmail().'", $subject, print_r($events, true));'
			);
			
			#register_shutdown_function($debEventsShutdownFunction, __METHOD__, &$requestEvents);
		}
		
		$requestEvents[] = array(
			'component'	=> $component,
			'event'		=> $event,
			'params'	=> $params
		);
	}
}

