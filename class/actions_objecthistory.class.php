<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2015 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    class/actions_objecthistory.class.php
 * \ingroup objecthistory
 * \brief   This file is an example hook overload class file
 *          Put some comments here
 */

/**
 * Class ActionsObjectHistory
 */
class ActionsObjectHistory
{
	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * @var array Errors
	 */
	public $errors = array();

	/**
	 * Constructor
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          &$action        Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	function doActions($parameters, &$object, &$action, $hookmanager)
	{
		global $db,$conf,$langs,$user;

		$TContext = explode(':',$parameters['context']);
		$THook = array(
			'propalcard'
			,'ordercard'
			,'invoicecard'
			,'supplier_proposalcard'
			,'ordersuppliercard'
			,'invoicesuppliercard'
		);

		$interSect = array_intersect($TContext, $THook);
		if (!empty($interSect))
		{
			if (!defined('INC_FROM_DOLIBARR')) define('INC_FROM_DOLIBARR', true);
			dol_include_once('/objecthistory/config.php');
			dol_include_once('/objecthistory/lib/objecthistory.lib.php');
			dol_include_once('/objecthistory/class/objecthistory.class.php');

			if (! empty($conf->global->OBJECTHISTORY_ARCHIVE_ON_MODIFY))
			{
				// CommandeFournisseur = reopen
				// FactureFournisseur = edit
				if (in_array($action, array('modif', 'reopen', 'edit')))
				{
					$action = 'objecthistory_modif';
					return 1; // on saute l'action par défaut en retournant 1, puis on affiche la pop-in dans formConfirm()
				}

				// Ask if proposal archive wanted
				if ($action == 'objecthistory_confirm_modify')
				{
					// New version if wanted
					$archive_object = GETPOST('archive_object', 'alpha');
					if ($archive_object == 'on')
					{
//						TPropaleHist::archiverPropale($ATMdb, $object);
						$res = ObjectHistory::archiveObject($object);

						if ($res > 0) setEventMessage($langs->trans('ObjectHistoryVersionSuccessfullArchived'));
						else setEventMessage($db->lasterror(), 'errors');
					}

					// CommandeFournisseur = reopen
					// FactureFournisseur = edit
					// On provoque le repassage-en brouillon avec l'action de base
					if ($object->element == 'order_supplier') $action = 'reopen';
					elseif ($object->element == 'invoice_supplier') $action = 'edit';
					else $action = 'modif';

					return 0; // Do standard code
				}
			}

			$actionATM = GETPOST('actionATM');
			if($actionATM == 'viewVersion')
			{
				$id = $object->id;

				if ($object->element == 'propal') $object = new PropalHistory($db);
				elseif ($object->element == 'commande') $object = new CommandeHistory($db);
				elseif ($object->element == 'facture') $object = new FactureHistory($db);
				elseif ($object->element == 'supplier_proposal') $object = new SupplierProposalHistory($db);
				elseif ($object->element == 'order_supplier') $object = new CommandeFournisseurHistory($db);
				elseif ($object->element == 'invoice_supplier') $object = new FactureFournisseurHistory($db);
				else
				{
					// Object not handled
					return 0;
				}

				$object->fetch($id);

				$version = new ObjectHistory($db);
				$version->fetch(GETPOST('idVersion'));
				$version->unserializeObject();

				if (!empty($object->fields))
				{
					foreach ($object->fields as $key => &$val)
					{
						$val = $version->serialized_object_source->{$key};
					}
				}
				else
				{
					foreach($version->serialized_object_source as $k => $v)
					{
						if ($k == 'db') continue;
						$object->{$k} = $v;
					}

					foreach($object->lines as &$line)
					{
						$line->description  = $line->desc;
						$line->db = $db;
						//$line->fetch_optionals();
					}
				}

				return 1;

			}
			elseif($actionATM == 'createVersion')
			{
				$res = ObjectHistory::archiveObject($object);

				if ($res > 0) setEventMessage($langs->trans('ObjectHistoryVersionSuccessfullArchived'));
				else setEventMessage($db->lasterror(), 'errors');

				header('Location: '.$_SERVER['PHP_SELF'].'?id='.$object->id);
				exit;

//				TPropaleHist::archiverPropale($ATMdb, $object);
			}
			elseif($actionATM == 'restaurer')
			{

				$res = ObjectHistory::restoreObject($object, GETPOST('idVersion'));
//				TPropaleHist::restaurerPropale($ATMdb, $object);

			}
			elseif($actionATM == 'supprimer')
			{
				$version = new ObjectHistory($db);
				$version->fetch(GETPOST('idVersion'));

				if ($version->delete($user) > 0) setEventMessage($langs->trans('ObjectHistoryVersionSuccessfullDelete'));
				else setEventMessage($db->lasterror(), 'errors');

				header('Location: '.$_SERVER['PHP_SELF'].'?id='.GETPOST('id'));
				exit;
			}


		}

		return 0;
	}

	function formConfirm($parameters, &$object, &$action, $hookmanager)
	{
		global $langs;
//		var_dump($this->showFormConfirmOnModif, $action);exit;
		if ($action == 'objecthistory_modif')
		{
			$langs->load('objecthistory@objecthistory');

			$form = new Form($this->db);
			$formConfirm = getFormConfirmObjectHistory($form, $object, $action);
			$this->results = array();
			$this->resprints = $formConfirm;

			return 1;
		}
	}

	function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
	{
		global $conf,$langs;

		$TContext = explode(':',$parameters['context']);
		$THook = array(
			'propalcard'
			,'ordercard'
			,'invoicecard'
			,'supplier_proposalcard'
			,'ordersuppliercard'
			,'invoicesuppliercard'
		);

		$interSect = array_intersect($TContext, $THook);
		if (!empty($interSect))
		{
			$langs->load('objecthistory@objecthistory');

			if (!defined('INC_FROM_DOLIBARR')) define('INC_FROM_DOLIBARR', true);
			dol_include_once('/objecthistory/config.php');
			dol_include_once('/objecthistory/lib/objecthistory.lib.php');
			dol_include_once('/objecthistory/class/objecthistory.class.php');

			$actionATM = GETPOST('actionATM');

			$TVersion = ObjectHistory::getAllVersionBySourceId($object->id, $object->element);
			print getHtmlListObjectHistory($object, $TVersion, $actionATM);

			$num = count($TVersion)+1; // TODO voir pour afficher le bon numéro de version si on est en mode visu
			if(!empty($num) && empty($conf->global->OBJECTHISTORY_HIDE_VERSION_ON_TABS))
			{
				print '<script type="text/javascript">
							$("#id-right div.tabsElem a:first").append(" / v.'.$num.'");
//							console.log($("#id-right div.tabsElem a:first"));
						</script>';
			}

			if ($actionATM == 'viewVersion') return 1;
		}

		return 0;
	}

}
