<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2020 SuperAdmin
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    dangerzone/admin/about.php
 * \ingroup dangerzone
 * \brief   About page of module DangerZone.
 */

// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res=@include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--; }
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/main.inc.php")) $res=@include substr($tmp, 0, ($i+1))."/main.inc.php";
if (! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php")) $res=@include dirname(substr($tmp, 0, ($i+1)))."/main.inc.php";
// Try main.inc.php using relative path
if (! $res && file_exists("../../main.inc.php")) $res=@include "../../main.inc.php";
if (! $res && file_exists("../../../main.inc.php")) $res=@include "../../../main.inc.php";
if (! $res) die("Include of main fails");

// Libraries
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once '../lib/colabor.lib.php';

// Translations
$langs->loadLangs(array("errors","admin","colabor@colabor"));

// Access control
if (! $user->admin) accessforbidden();

// Parameters
$action = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');


/*
 * View
 */

$form = new Form($db);

$page_name = $langs->trans("ColaborAboutPage");
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="'.($backtopage?$backtopage:DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'object_colabor@colabor');

// Configuration header
$head = colaborAdminPrepareHead();
dol_fiche_head($head, 'about', '', 0, 'colabor@colabor');

dol_include_once('/colabor/core/modules/modColabor.class.php');
$tmpmodule = new modColabor($db);

	print '<div class="imagenAbout">';
	print '<img src="../img/machfree.jpg">';
	print '</div>';

	print '<div class="link" >';
	print '<a href="https://machfree.com/dolifac-erp" target="_blank">Desarrollado: MACHFREE TECNOLOGY E.I.R.L</a> <br>';
	print '<a href="https://machfree.com" target="_blank">Web: Machfree.com</a> <br>';
	print '<a href="mailto:sistemas@machfree.com" target="_blank">sistemas@machfree.com</a>';
	print '</div>';

	print '<div >';
	print '<p> El módulo Colabor sirve para realizar anotaciones dentro de las fichas de cada producto,tercero, pedido,etc. De forma colaborativa, con opciones de edicion o eliminación segun permisos. Cada nota llevará el registro de fecha y usuario que realizo dicha acción.  </p>';
	print '</div>';

// Page end
dol_fiche_end();
llxFooter();
$db->close();
