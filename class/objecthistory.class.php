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

	/** @var int $fk_source */
	public $fk_source;
	/** @var string $element_source */
	public $element_source;
	/** @var int $date_version */
	public $date_version;
	/** @var double $total */
	public $total;
	/** @var int $entity */
	public $entity;
	/** @var Propal|Commande|Facture|SupplierProposal|CommandeFournisseur|FactureFournisseur|string $serialized_object_source */
	public $serialized_object_source;

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
