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
		global $db,$conf,$langs;

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
				// TODO commande fourn = reopen
				// TODO facture fourn = edit
				if (in_array($action, array('modif', 'reopen', 'edit'))) {
					return 1; // on saute l'action par défaut en retournant 1, puis on affiche la pop-in dans formConfirm()
				}

				// Ask if proposal archive wanted
				if ($action == 'propalhistory_confirm_modify') {

					// New version if wanted
					$archive_proposal = GETPOST('archive_proposal', 'alpha');
					if ($archive_proposal == 'on') {
						TPropaleHist::archiverPropale($ATMdb, $object);
					}
					$action = 'modif'; // On provoque le repassage-en brouillon

					return 0; // Do standard code
				}
			}



			$actionATM = GETPOST('actionATM');
			if($actionATM == 'viewVersion')
			{
				$version = new ObjectHistory($db);
				$version->fetch(GETPOST('idVersion'));
				$version->unserializeObject();

//				$obj = $version->serialized_object_source;
				foreach ($object->fields as $key => &$val)
				{
					$val = $version->serialized_object_source->{$key};
				}

//				$propal = $version->getObject();
//				//pre($propal,true);
//
//				$object = new PropalHist($db, $object->socid);
//				foreach($propal as $k=>$v) $object->{$k} = $v;
//
//				foreach($object->lines as &$line) {
//					$line->description  = $line->desc;
//					$line->db =  $db;
//					//$line->fetch_optionals();
//				}

				//pre($object,true);
//				$object->id = $_REQUEST['id'];
//				$object->db = $db;
			} elseif($actionATM == 'createVersion') {

				TPropaleHist::archiverPropale($ATMdb, $object);

			} elseif($actionATM == 'restaurer') {

				TPropaleHist::restaurerPropale($ATMdb, $object);

			} elseif($actionATM == 'supprimer') {

				$version = new TPropaleHist;
				$version->load($ATMdb, $_REQUEST['idVersion']);
				$version->delete($ATMdb);

				?>
				<script language="javascript">
					document.location.href="<?php echo $_SERVER['PHP_SELF'] ?>?id=<?php echo $_REQUEST['id']?>&mesg=<?php echo $langs->transnoentities('HistoryVersionSuccessfullDelete') ?>";
				</script>
				<?php

			}


		}




		return 0;
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
	function formObjectOptions($parameters, &$object, &$action, $hookmanager)
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
			if (!defined('INC_FROM_DOLIBARR')) define('INC_FROM_DOLIBARR', true);
			dol_include_once('/objecthistory/config.php');
			dol_include_once('/objecthistory/lib/objecthistory.lib.php');
			dol_include_once('/objecthistory/class/objecthistory.class.php');

			if($action != 'create' && $action != 'statut' && $action != 'presend')
			{
				$langs->load('objecthistory@objecthistory');

				$actionATM = GETPOST('actionATM');
				$from = $_SERVER['HTTP_REFERER'];

				if($actionATM == 'viewVersion')
				{
					?>
					<script type="text/javascript">
						$(document).ready(function() {
							$('div.tabsAction').html('<?php echo '<div><a id="returnCurrent" href="'.$_SERVER['PHP_SELF'].'?id='.$_REQUEST['id'].'">'.$langs->trans('ReturnInitialVersion').'</a> <a id="butRestaurer" class="butAction" href="'.$from.'?id='.$_REQUEST['id'].'&actionATM=restaurer&idVersion='.$_REQUEST['idVersion'].'">'.$langs->trans('Restaurer').'</a><a id="butSupprimer" class="butAction" href="'.$from.'?id='.$_REQUEST['id'].'&actionATM=supprimer&idVersion='.$_REQUEST['idVersion'].'">'.$langs->trans('Delete').'</a></div>'?>');
							$('#butRestaurer').insertAfter('#voir');
							$('#butSupprimer').insertBefore('#voir');
							$('#builddoc_form').hide();
						})
					</script>

					<?php

					$TVersion = ObjectHistory::getAllVersionBySourceId($object->id, $object->element);
					print getHtmlListObjectHistory($object, $TVersion);
//					TPropaleHist::listeVersions($db, $object);
				} elseif($actionATM == 'createVersion') {
					$TVersion = ObjectHistory::getAllVersionBySourceId($object->id, $object->element);
					print getHtmlListObjectHistory($object, $TVersion);
//					TPropaleHist::listeVersions($db, $object);
				} elseif($actionATM == '' && $object->statut == 1) {
					print '<a id="butNewVersion" class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$_REQUEST['id'].'&actionATM=createVersion">'.$langs->trans('ObjectHistoryArchiver').'</a>';
					?>
					<script type="text/javascript">
						$(document).ready(function() {
							$("#butNewVersion").appendTo('div.tabsAction');
						})
					</script>
					<?php
//					$num = TPropaleHist::listeVersions($db, $object);
					$TVersion = ObjectHistory::getAllVersionBySourceId($object->id, $object->element);
					print getHtmlListObjectHistory($object, $TVersion);
				}
				else {

//					$num = TPropaleHist::listeVersions($db, $object);
					$TVersion = ObjectHistory::getAllVersionBySourceId($object->id, $object->element);
					print getHtmlListObjectHistory($object, $TVersion);


				}
				if(!empty($TVersion) && ! $conf->global->OBJECTHISTORY_HIDE_VERSION_ON_TABS) {
					?>
					<script type="text/javascript">
						$("a#comm").first().append(" / v. <?php echo (count($TVersion)+1) ?>");
						console.log($("a#comm").first());
					</script>
					<?php

				}
			}
		}

		return 0;
	}
}
