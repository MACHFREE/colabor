<?php
/* Copyright (C) 2018 Nicolas ZABOURI   <info@inovea-conseil.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/modulebuilder/admin/setup.php
 *  \ingroup    modulebuilder
 *  \brief      Page setup for modulebuilder module
 */
$res=0;
if (! $res && file_exists("../../main.inc.php")) 
    $res=@include("../../main.inc.php");                    // For root directory
if (! $res && file_exists("../../../main.inc.php")) 
    $res=@include("../../../main.inc.php"); // For "custom" directory

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once '../lib/colabor.lib.php';

global $conf, $langs, $user, $db;
$langs->loadLangs(array("admin", "colabor@colabor",'commercial', 'bills', 'orders', 'contracts','supplier_proposals','compta','propal'));

$action = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$value = GETPOST('value', 'alpha');


if (!$user->admin )
    accessforbidden();


/*
 * Actions
 */

if ($action == 'setprofidmandatory')
{
    $status = GETPOST('status', 'alpha');

    $idprof = "colabor_".$value;
    if (dolibarr_set_const($db, $idprof, $status, 'chaine', 0, '', $conf->entity) > 0)
    {
        //header("Location: ".$_SERVER["PHP_SELF"]);
        //exit;
    }
    else
    {
        dol_print_error($db);
    }
}

/*
 *  View
 */

$form = new Form($db);
$page_name = $langs->trans("ColaborSetupPage");
llxHeader('', $langs->trans($page_name));

$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php').'">'.$langs->trans("BackToModuleList").'</a>';


print load_fiche_titre($langs->trans($page_name), $linkback, 'object_colabor@colabor');

if (GETPOST('withtab', 'alpha')) {
    dol_fiche_head($head, 'modulebuilder', '', -1);
}

// Configuration header
$head = colaborAdminPrepareHead();
dol_fiche_head($head, 'settings', '', -1, "colabora@colabora");

print '<div class="div-table-responsive-no-min">';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
        print '<td>'.$langs->trans("Name").'</td>';
        print '<td>'.$langs->trans("Description").'</td>';
        print '<td class="center">'.$langs->trans("Value").'</td>';
    print "</tr>\n";

    $profid['Thirparties'][0] = $langs->trans("ThirdParty");
    $profid['Thirparties'][1] = $langs->transcountry('ThirdParty', $mysoc->country_code);
    $profid['Products'][0] = $langs->trans("Products").'/'.$langs->trans("Services");
    $profid['Products'][1] = $langs->transcountry('Products', $mysoc->country_code).'/'.$langs->transcountry('Services', $mysoc->country_code);
    $profid['Orders'][0] = $langs->trans("Orders");
    $profid['Orders'][1] = $langs->transcountry('Orders', $mysoc->country_code);
    $profid['Proposals'][0] = $langs->trans("Proposals");
    $profid['Proposals'][1] = $langs->transcountry('Proposals', $mysoc->country_code);
    $profid['Bills'][0] = $langs->trans("Bills");
    $profid['Bills'][1] = $langs->transcountry('Bills', $mysoc->country_code);
    $profid['Project'][0] = $langs->trans("Project");
    $profid['Project'][1] = $langs->transcountry('Project', $mysoc->country_code);

    if (!empty($conf->sertega) || !empty($conf->sertega->enabled)) {
        $profid['Sertega'][0] = $langs->trans("Sertega");
        $profid['Sertega'][1] = $langs->transcountry('Sertega', $mysoc->country_code);        
    }

    if (!empty($conf->preopportunity) || !empty($conf->preopportunity->enabled)) {
        $profid['Preopportunity'][0] = $langs->trans("Preopportunity");
        $profid['Preopportunity'][1] = $langs->transcountry('Preopportunity', $mysoc->country_code);        
    }

     if (!empty($conf->waranti) || !empty($conf->waranti->enabled)) {
        $profid['Waranti'][0] = $langs->trans("Waranti");
        $profid['Waranti'][1] = $langs->transcountry('Waranti', $mysoc->country_code);        
    }


    


    $nbofloop = count($profid);
    foreach ($profid as $key => $val)
    {
        if ($profid[$key][1] != '-')
        {
            print '<tr class="oddeven">';
                print '<td>'.$profid[$key][0]."</td><td>\n";
                print $profid[$key][1];
                print '</td>';

                $idprof_mandatory = 'colabor_'.$key;
                $mandatory = (empty($conf->global->$idprof_mandatory) ?false:true);


                if ($mandatory)
                {
                    print '<td class="center"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setprofidmandatory&value='.$key.'&status=0">';
                    print img_picto($langs->trans("Activated"), 'switch_on');
                    print '</a></td>';
                }
                else
                {
                    print '<td class="center"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setprofidmandatory&value='.$key.'&status=1">';
                    print img_picto($langs->trans("Disabled"), 'switch_off');
                    print '</a></td>';
                }   

            print "</tr>\n";
        }
        $i++;
    }

    print "</table>\n";
print '</div>';



if (GETPOST('withtab', 'alpha')) {
    dol_fiche_end();
}

print '</form>';

// End of page
llxFooter();
$db->close();
