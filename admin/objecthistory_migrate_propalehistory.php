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
 * 	\file		admin/about.php
 * 	\ingroup	objecthistory
 * 	\brief		This file is an example about page
 * 				Put some comments here
 */
// Dolibarr environment
$res = @include("../../main.inc.php"); // From htdocs directory
if (! $res) {
    $res = @include("../../../main.inc.php"); // From "custom" directory
}

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once '../class/objecthistory.class.php';
require_once '../lib/objecthistory.lib.php';

// Translations
$langs->load("objecthistory@objecthistory");

// Access control
if (! $user->admin) {
    accessforbidden();
}
$object = new ObjectHistory($db);

$action = GETPOST('action');

if ($action == 'confirm_migrate' && !empty($user->admin))
{
	set_time_limit(0);
	require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
	dol_include_once('/propalehistory/class/propaleHist.class.php');
	$PDOdb = new TPDOdb();

	$sql = 'SELECT ph.rowid, p.entity FROM '.MAIN_DB_PREFIX.'propale_history ph
			INNER JOIN '.MAIN_DB_PREFIX.'propal p ON (p.rowid = ph.fk_propale)
			WHERE NOT EXISTS (
				SELECT o.rowid FROM '.MAIN_DB_PREFIX.'objecthistory o WHERE o.element_source = \'propal\' AND o.fk_source = ph.fk_propale 
			)
			';

	$resql = $db->query($sql);
	if ($resql)
	{
		$db->begin();
		$error = 0;
		while ($obj = $db->fetch_object($resql))
		{
			$propalehistory = new TPropaleHist();
			$propalehistory->load($PDOdb, $obj->rowid);
			if ($propalehistory->getId() > 0)
			{
				$o = new ObjectHistory($db);
				$o->fk_source = $propalehistory->fk_propale;
				$o->element_source = 'propal';
				$o->date_version = $propalehistory->date_version;
				$o->total = $propalehistory->total;
				$o->entity = $obj->entity;
				$o->serialized_object_source = $propalehistory->serialized_parent_propale;
				$o->date_creation = $propalehistory->date_cre; // TODO voir si ça fonctionne

				$res = $o->create($user);
				if ($res <= 0)
				{
					$error++;
					break;
				}
			}
		}

		if ($error)
		{
			$db->rollback();
			setEventMessage($langs->trans('objecthistory_errorDuringMigration'), $o->db->lasterror(), 'errors');
		}
		else
		{
			$db->commit();
			setEventMessage($langs->trans('objecthistory_migrationSuccess'));
			header('Location: '.dol_buildpath('/objecthistory/admin/objecthistory_migrate_propalehistory.php', 1));
		}
	}
	else
	{
		dol_print_error($db);
		exit;
	}

}

$nb_line = $nb_line_already_backuped = 0;

$sql = 'SELECT count(rowid) AS nb FROM '.MAIN_DB_PREFIX.'propale_history';
$resql = $db->query($sql);
if ($resql)
{
	$obj = $db->fetch_object($resql);
	$nb_line = $obj->nb;
}
else
{
	dol_print_error($db);
	exit;
}


$form = new Form($db);
$formconfirm = getFormConfirmObjectHistory($form, $object, $action);

/*
 * View
 */
$page_name = "MigrateObjectHistory";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
    . $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans($page_name), $linkback);

// Configuration header
$head = objecthistoryAdminPrepareHead();
dol_fiche_head(
    $head,
    'propalehistory',
    $langs->trans("Module104090Name"),
    0,
    'objecthistory@objecthistory'
);

print '<p>'.$langs->trans('objecthistory_nb_line', $nb_line).'</p>';

if ($nb_line > $nb_line_already_backuped)
{
	print '
		<div class="tabsAction">
			<div class="inline-block divButAction">
				<a class="butAction" href="'.dol_buildpath('/objecthistory/admin/objecthistory_migrate_propalehistory.php', 1).'?action=migrate">'.$langs->trans('objecthistory_action_migrate').'</a>
			</div>
		</div>';
}

if (!empty($formconfirm)) print $formconfirm;
llxFooter();

$db->close();
