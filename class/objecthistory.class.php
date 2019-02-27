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

	public function unserializeObject()
	{
		$code = @gzinflate(base64_decode($this->serialized_object_source));
		if($code === false) $code = $this->serialized_object_source;

		$code = unserialize($code);
		if($code === false) $code = unserialize(utf8_decode($code));

		$this->serialized_object_source = $code;
	}

	public function serializeObject(&$object)
	{
		global $conf;

		$code = serialize($object);
		if(!empty($conf->global->OBJECTHISTORY_USE_COMPRESS_ARCHIVE)) $code = base64_encode( gzdeflate($code) );

		$this->serialized_object_source = $code;
	}

	/**
	 * @param int $fk_source
	 * @param string $element_source
	 * @return ObjectHistory[]
	 */
	public static function getAllVersionBySourceId($fk_source, $element_source)
	{
		global $db;

		$sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'objecthistory WHERE fk_source = '.$fk_source.' AND element_source = \''.$db->escape($element_source).'\'';
		$resql = $db->query($sql);
		if ($resql)
		{
			$TRes = array();
			while ($obj = $db->fetch_object($resql))
			{
				$o = new ObjectHistory($db);
				$o->fetch($obj->rowid);
				$TRes[$o->id] = $o;
			}

			return $TRes;
		}
		else
		{
			dol_print_error($db);
			exit;
		}
	}

	public static function archiveObject(&$object)
	{
		global $db,$conf,$user;

		if (!empty($conf->global->OBJECTHISTORY_ARCHIVE_PDF_TOO)) self::archivePDF($object);

		$newVersion = new ObjectHistory($db);
		$newVersion->serializeObject($object);

		$newVersion->fk_source = $object->id;
		$newVersion->element_source = $object->element;
		$newVersion->date_version = dol_now();
		$newVersion->total = $object->total_ht;
		$newVersion->entity = $object->entity;

		return $newVersion->create($user);
	}

	static function archivePDF(&$object)
	{
		global $db;

		$sql = " SELECT count(*) as nb";
		$sql.= " FROM ".MAIN_DB_PREFIX."objecthistory";
		$sql.= " WHERE fk_source = ".$object->id;
		$sql.= " WHERE element_source = '".$db->escape($object->element)."'";
		$resql = $db->query($sql);

		$nb=1;
		if ($resql && ($row = $db->fetch_object($resql))) $nb = $row->nb + 1;

		$ok = 1;

		// TODO init le bon dossier en fonction de l'objet
		if ($object->entity > 1) {
			$filename = DOL_DATA_ROOT . '/' . $object->entity . '/propale/' . $object->ref . '/' .$object->ref;
			$path = DOL_DATA_ROOT . '/' . $object->entity . '/propale/' . $object->ref . '/' .$object->ref . '.pdf';
		}
		else {
			$filename = DOL_DATA_ROOT . '/propale/' . $object->ref . '/' .$object->ref;
			$path = DOL_DATA_ROOT . '/propale/' . $object->ref . '/' .$object->ref . '.pdf';
		}

		if (!is_file($path)) $ok = self::generatePDF($object);

		if ($ok > 0)
		{
			exec('cp "'.$path.'" "'.$filename.'-'.$nb.'.pdf"');
		}
	}

	static function generatePDF(&$object)
	{
		global $conf,$langs;

		if (method_exists($object, 'generateDocument'))
			return $object->generateDocument($conf->global->PROPALE_ADDON_PDF, $langs, 0, 0, 0);

		return false;
	}
}

require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
class PropalHistory extends Propal
{
	/** @override */
	function getLinesArray()
	{
		return null;
	}

}

require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
class CommandeHistory extends Commande
{
	/** @override */
	function getLinesArray()
	{
		return null;
	}

}

require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
class FactureHistory extends Facture
{
	/** @override */
	function getLinesArray()
	{
		return null;
	}

}

require_once DOL_DOCUMENT_ROOT.'/supplier_proposal/class/supplier_proposal.class.php';
class SupplierProposalHistory extends SupplierProposal
{
	/** @override */
	function getLinesArray()
	{
		return null;
	}

}

// TODO check le fonctionnement => getLinesArray() NOT FOUND
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
class CommandeFournisseurHistory extends CommandeFournisseur
{
//	/** @override */
//	function getLinesArray()
//	{
//		return null;
//	}

}

// TODO check le fonctionnement => getLinesArray() NOT FOUND
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
class FactureFournisseurHistory extends FactureFournisseur
{
//	/** @override */
//	function getLinesArray()
//	{
//		return null;
//	}

}
