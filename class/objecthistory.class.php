<?php

if (!class_exists('SeedObject'))
{
	/**
	 * Needed if $form->showLinkedObjectBlock() is call
	 */
	define('INC_FROM_DOLIBARR', true);
	require_once dirname(__FILE__).'/../config.php';
}


class ObjectHistory extends SeedObject
{
	public $table_element = 'objecthistory';

	public $element = 'objecthistory';
	
	public function __construct($db)
	{
		global $conf;
		
		$this->db = $db;
		
		$this->fields=array(
				'fk_source'=>array('type'=>'integer','index'=>true)
				,'element_source'=>array('type'=>'string')
				,'date_version'=>array('type'=>'date') // date, integer, string, float, array, text
				,'total'=>array('type'=>'double')
				,'entity'=>array('type'=>'integer','index'=>true)
				,'serialized_object_source'=>array('type'=>'text')
		);
		
		$this->init();

		$this->entity = $conf->entity;
	}

}
