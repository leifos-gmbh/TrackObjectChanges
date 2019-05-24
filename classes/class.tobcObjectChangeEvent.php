<?php

/**
 * Class to manage one object change event
 *
 * @author		Björn Heyser <bheyser@databay.de>
 * @author  Stefan Meyer <smeyer.ilias@gmx.de>
 *
 */
class tobcObjectChangeEvent
{
	/**
	 * @var string[]
	 */
	private static $trackedObjectTypes = array('root','cat', 'grp', 'fold', 'htlm', 'file','sahs','webr', 'lm', 'blog', 'itgr');


	/**
	 * @var string[]
	 */
	private static $allowedContainerTypes = array('root','cat','grp','fold');
	
	const EVENT_TYPE_CREATE  = 'CREATE';
	const EVENT_TYPE_UPDATE  = 'UPDATE';
	const EVENT_TYPE_REMOVE  = 'REMOVE';
	const EVENT_TYPE_TOTRASH = 'TOTRASH';
	const EVENT_TYPE_RESTORE = 'RESTORE';

	/**
	 * @var int
	 */
	private $id = null;

	/**
	 * @var int
	 */
	private $objId = null;

	/**
	 * @var string
	 */
	private $objType = null;

	/**
	 * @var string
	 */
	private $eventType = null;

	/**
	 * @var string
	 */
	private $eventDate = null;

	/**
	 * tobcObjectChangeEvent constructor.
	 * @param null $id
	 * @throws ilException
	 */
	public function __construct($id = null)
	{
		if( !is_null($id) )
		{
			$this->setId($id);
			$this->read();
		}
	}

	/**
	 * Read entry
	 * @return bool
	 * @throws ilException
	 */
	public function read()
	{
		global $DIC;

		$ilDB = $DIC->database();

		if( !$this->getId() )
		{
			throw new ilException('Cannot read object change event without valid id!');
		}
		
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

	/**
	 * Assign datset
	 * @param array $dataSet
	 * @return tobcObjectChangeEvent
	 */
	public function assign($dataSet)
	{
		$this->setId($dataSet['id']);
		
		$this->setObjId($dataSet['obj_id']);
		$this->setObjType($dataSet['obj_type']);
		
		$this->setEventType($dataSet['evt_type']);
		$this->setEventDate($dataSet['evt_date']);
		
		return $this;
	}

	/**
	 * Save entry
	 */
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

	/**
	 * Create new entry
	 */
	private function insert()
	{
		global $DIC;

		$ilDB = $DIC->database();
		
		$nextId = $ilDB->nextId('evnt_evhk_tobc_events');
		
		$ilDB->insert('evnt_evhk_tobc_events', array(
			'evt_id'			=> array('integer', $nextId),
			'evt_obj_id'		=> array('integer', $this->getObjId()),
			'evt_obj_type'		=> array('text', $this->getObjType()),
			'evt_event_type'	=> array('text', $this->getEventType()),
			'evt_event_date'	=> array('timestamp', $this->getEventDate())
		));
	}

	/**
	 * Update entry
	 */
	private function update()
	{
		global $DIC;

		$ilDB = $DIC->database();
		
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

	/**
	 * Delete entry
	 * @return bool
	 * @throws ilException
	 */
	public function delete()
	{
		global $DIC;

		$ilDB = $DIC->database();

		if( !$this->getId() )
		{
			throw new ilException('Cannot delete object change event without valid id!');
		}
		
		$query = "
			DELETE FROM		evnt_evhk_tobc_events

			WHERE			evt_id = %s
		";
		
		$affectedRows = $ilDB->manipulateF(
			$query, array('integer'), array($this->getId())
		);
		
		if( $affectedRows > 1 )
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

	/**
	 * @param int $id
	 * @return $this
	 */
	public function setId($id)
	{
		$this->id = (int)$id;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getObjId()
	{
		return $this->objId;
	}

	/**
	 * @param int $objId
	 * @return $this
	 */
	public function setObjId($objId)
	{
		$this->objId = (int)$objId;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getObjType()
	{
		return $this->objType;
	}

	/**
	 * @param string $objType
	 * @return $this
	 */
	public function setObjType($objType)
	{
		$this->objType = $objType;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getEventType()
	{
		return $this->eventType;
	}

	/**
	 * @param string $eventType
	 * @return $this
	 */
	public function setEventType($eventType)
	{
		$this->eventType = $eventType;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getEventDate()
	{
		return $this->eventDate;
	}

	/**
	 * @param string $eventDate
	 * @return $this
	 */
	public function setEventDate($eventDate)
	{
		$this->eventDate = $eventDate;
		return $this;
	}

	/**
	 * @return string[]
	 */
	public static function getTrackedObjectTypes()
	{
		return self::$trackedObjectTypes;
	}

	/**
	 * @return string[]
	 */
	public static function getAllowedContainerTypes()
	{
		return self::$allowedContainerTypes;
	}

	/**
	 * @return string[]
	 */
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

