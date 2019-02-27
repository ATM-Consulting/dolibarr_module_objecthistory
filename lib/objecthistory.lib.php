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
 *	\file		lib/objecthistory.lib.php
 *	\ingroup	objecthistory
 *	\brief		This file is an example module library
 *				Put some comments here
 */

function objecthistoryAdminPrepareHead()
{
    global $langs, $conf;

    $langs->load("objecthistory@objecthistory");

    $h = 0;
    $head = array();

	$head[$h][0] = dol_buildpath("/objecthistory/admin/objecthistory_setup.php", 1);
	$head[$h][1] = $langs->trans("Parameters");
	$head[$h][2] = 'settings';
	$h++;

	$res = dol_include_once('/propalehistory/config.php');
	if ($res)
	{
		$head[$h][0] = dol_buildpath("/objecthistory/admin/objecthistory_migrate_propalehistory.php", 1);
		$head[$h][1] = $langs->trans("Module104090Name");
		$head[$h][2] = 'propalehistory';
		$h++;
	}

    $head[$h][0] = dol_buildpath("/objecthistory/admin/objecthistory_about.php", 1);
    $head[$h][1] = $langs->trans("About");
    $head[$h][2] = 'about';
    $h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    //$this->tabs = array(
    //	'entity:+tabname:Title:@objecthistory:/objecthistory/mypage.php?id=__ID__'
    //); // to add new tab
    //$this->tabs = array(
    //	'entity:-tabname:Title:@objecthistory:/objecthistory/mypage.php?id=__ID__'
    //); // to remove a tab
    complete_head_from_modules($conf, $langs, $object, $head, $h, 'objecthistory');

    return $head;
}

/**
 * Return array of tabs to used on pages for third parties cards.
 *
 * @param 	ObjectHistory	$object		Object company shown
 * @return 	array				Array of tabs
 */
function objecthistory_prepare_head(ObjectHistory $object)
{
    global $db, $langs, $conf, $user;
    $h = 0;
    $head = array();
    $head[$h][0] = dol_buildpath('/objecthistory/card.php', 1).'?id='.$object->id;
    $head[$h][1] = $langs->trans("ObjectHistoryCard");
    $head[$h][2] = 'card';
    $h++;
	
	// Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@objecthistory:/objecthistory/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname:Title:@objecthistory:/objecthistory/mypage.php?id=__ID__');   to remove a tab
    complete_head_from_modules($conf,$langs,$object,$head,$h,'objecthistory');
	
	return $head;
}

/**
 * @param Form $form
 * @param ObjectHistory $object
 * @param string $action
 * @return string
 */
function getFormConfirmObjectHistory(&$form, &$object, $action)
{
    global $langs,$user;

    $formconfirm = '';

    if ($action == 'migrate' && !empty($user->admin))
    {
        $text = $langs->trans('ConfirmMigrateObjectHistory');
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'], $langs->trans('MigrateObjectHistory'), $text, 'confirm_migrate', '', 0, 1);
    }
//    elseif ($action == 'delete' && !empty($user->rights->objecthistory->write))
//    {
//        $text = $langs->trans('ConfirmDeleteObjectHistory');
//        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('DeleteObjectHistory'), $text, 'confirm_delete', '', 0, 1);
//    }
//    elseif ($action == 'clone' && !empty($user->rights->objecthistory->write))
//    {
//        $text = $langs->trans('ConfirmCloneObjectHistory', $object->ref);
//        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('CloneObjectHistory'), $text, 'confirm_clone', '', 0, 1);
//    }

    return $formconfirm;
}

/**
 * @param Propal|Commande|Facture|SupplierProposal|CommandeFournisseur|FactureFournisseur $object
 * @param ObjectHistory[] $TVersion
 * @return string
 */
function getHtmlListObjectHistory($object, $TVersion, $actionATM)
{
	global $db,$conf,$langs;

	$html = '';

	if (!empty($TVersion))
	{
		if($actionATM == 'viewVersion') $html.= '<div class="linkback" style="margin:15px"><a id="returnCurrent" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">'.$langs->trans('ReturnInitialVersion').'</a></div>';

		$html.= '<div class="inline-block divButAction">';
		$html.= '<form name="formVoirPropale" method="POST" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
		$html.= '<input type="hidden" name="actionATM" value="viewVersion" />';
		$html.= '<input type="hidden" name="socid" value="'.(!empty($object->fk_soc) ? $object->fk_soc : $object->socid).'" />';
		$html.= '<select name="idVersion" class="flat">';

		$i = 1;
		$idVersion = GETPOST('idVersion', 'int');
		foreach($TVersion as &$objecthistory)
		{
			if($idVersion == $objecthistory->id) $selected = 'selected="selected"';
			else $selected = "";

			$html.= '<option id="'.$objecthistory->id.'" value="'.$objecthistory->id.'" '.$selected.'>Version n° '.$i.' - '.price($objecthistory->total).' '.$langs->getCurrencySymbol($conf->currency,0).' - '.dol_print_date($objecthistory->date_creation, "dayhour").'</option>';

			$i++;
		}

		$html.= '</select>';
		$html.= '<input class="butAction" id="voir" value="'.$langs->trans('Visualiser').'" type="SUBMIT" />';
		$html.= '</form>';
		$html.= '</div>';

		$html.= ' | ';


		if($actionATM == 'viewVersion')
		{
			$html.= '<div class="inline-block divButAction"><a id="butRestaurer" class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&actionATM=restaurer&idVersion='.GETPOST('idVersion').'">'.$langs->trans('Restaurer').'</a></div>';
			$html.= '<div class="inline-block divButAction"><a id="butSupprimer" class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&actionATM=supprimer&idVersion='.GETPOST('idVersion').'">'.$langs->trans('Delete').'</a></div>';
		}
	}

	if($actionATM == '' && $object->statut == 1) $html.= '<div class="inline-block divButAction"><a id="butNewVersion" class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&actionATM=createVersion">'.$langs->trans('ObjectHistoryArchiver').'</a></div>';

	return $html;
}
