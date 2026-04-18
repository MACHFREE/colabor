<?php
/* Copyright (C) 2004-2017  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2019  Frédéric France     <frederic.france@netlogic.fr>
 * Copyright (C) ---Put here your own copyright and developer email---
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
 * \file    htdocs/modulebuilder/template/core/boxes/mymodulewidget1.php
 * \ingroup mymodule
 * \brief   Widget provided by MyModule
 *
 * Put detailed description here.
 */

/** Includes */
include_once DOL_DOCUMENT_ROOT . "/core/boxes/modules_boxes.php";
dol_include_once("/colabor/class/colabor.class.php");
dol_include_once("/projet/class/project.class.php");

/**
 * Class to manage the box
 *
 * Warning: for the box to be detected correctly by dolibarr,
 * the filename should be the lowercase classname
 */
class colabor_box_project_3 extends ModeleBoxes
{
	/**
	 * @var string Alphanumeric ID. Populated by the constructor.
	 */
	public $boxcode = "colabor";

	/**
	 * @var string Box icon (in configuration page)
	 * Automatically calls the icon named with the corresponding "object_" prefix
	 */
	public $boximg = "colabor@colabor";

	/**
	 * @var string Box label (in configuration page)
	 */
	public $boxlabel;

	/**
	 * @var string[] Module dependencies
	 */
	public $depends = array('colabor');

	/**
	 * @var DoliDb Database handler
	 */
	public $db;

	/**
	 * @var mixed More parameters
	 */
	public $param;

	/**
	 * @var array Header informations. Usually created at runtime by loadBox().
	 */
	public $info_box_head = array();

	/**
	 * @var array Contents informations. Usually created at runtime by loadBox().
	 */
	public $info_box_contents = array();

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 * @param string $param More parameters
	 */
	public function __construct(DoliDB $db, $param = '')
	{
		global $user, $conf, $langs;
		$langs->load("boxes");
		$langs->load('colabor@colabor');

		parent::__construct($db, $param);

		$this->boxlabel = "Colabor Seguimiento";

		$this->param = $param;

		//$this->enabled = $conf->global->FEATURES_LEVEL > 0;         // Condition when module is enabled or not
		//$this->hidden = ! ($user->rights->mymodule->myobject->read);   // Condition when module is visible by user (test on permission)
	}

	/**
	 * Load data into info_box_contents array to show array later. Called by Dolibarr before displaying the box.
	 *
	 * @param int $max Maximum number of records to load
	 * @return void
	 */
	public function loadBox($max = 50)
	{
		global $conf, $user, $langs, $db;

		$project = new Project($db);
		$colabor = new Colabor($db);
		$societe = new Societe($db);
		$product = new Product($db);
		$extrafields = new extrafields($db);
		// $user = new User($db);

		// Use configuration value for max lines count
		$this->max = $max;

		//include_once DOL_DOCUMENT_ROOT . "/mymodule/class/mymodule.class.php";

		// Populate the head at runtime
		$text = $langs->trans("followupfile", $max);
		$this->info_box_head = array(
			// Title text
			'text' => $text,
			// Add a link
			'sublink' => 'http://dolifac.com',
			// Sublink icon placed after the text
			'subpicto' => 'colabor@colabo',
			// Sublink icon HTML alt text
			'subtext' => 'machfree',
			// Sublink HTML target
			'target' => 'twocolumns',
			// HTML class attached to the picto and link
			'class' => 'child',
			'subclass' => 'twocolumns',
			// Limit and truncate with "…" the displayed text lenght, 0 = disabled
			'limit' => 0,
			// Adds translated " (Graph)" to a hidden form value's input (?)
			'graph' => false
		);

		if($user->rights->colabor->read){

			$sql = "SELECT DISTINCT pr.rowid as prowid ,  (select MAX(co.datec) FROM llx_colabor as co WHERE co.fk_element=pr.rowid) as fecha_accion, (SELECT MAX(rowid) FROM llx_colabor  WHERE fk_element=pr.rowid GROUP BY fk_element) as crowid ";
			$sql .= "FROM ".MAIN_DB_PREFIX."colabor as co INNER JOIN ".MAIN_DB_PREFIX."projet as pr";
			$sql .= " WHERE co.fk_element =pr.rowid GROUP by pr.rowid, crowid";
			$sql.= $db->order("fecha_accion", "DESC");
			$sql.= $db->plimit($max, 0);

			$result = $db->query($sql);

			if ($result) {
				$num = $db->num_rows($result);
				$i = 0;
				while ($i < $num) {

					$objp = $db->fetch_object($result);

					$colabor->fetch($objp->crowid);
					$project->fetch($objp->prowid);
					$societe->fetch($project->socid);
					$product->fetch($project->title);

                    // $extralabels = $extrafields->fetch_name_optionals_label($product->table_element, true);
                    // $product->fetch_optionals($product->id,$extralabels);

                    
                    $now = dol_now();
					// $datec= new DateTime($project->array_options ["options_datep"]);
					// $diff = $now->diff($datec);
					print $diff;
					if($diff >= 90) $oddeven1='oddeven1';

					// $entrepot_static->fetch($objp->fk_entrepot);
					$valor= $product->array_options ["options_seguim"];
                    // if( $valor >= 1){
                    	
						$this->info_box_contents[$i][0] = array('td' => 'align="left" class="'.$oddeven1.'"',
							'text' => $project->ref,
							'logo' => 'project',
							'url' => DOL_URL_ROOT."/projet/card.php?id=".$project->id);
						
						$this->info_box_contents[$i][1] = array('td' => 'align="left" class="'.$oddeven1.'"',
							'text' =>$product->array_options ["options_seguim"] );


						$this->info_box_contents[$i][2] = array('td' => 'align="left" class="'.$oddeven1.'"',
							'text' => $societe->getNomUrl(1, ''),
							'url' => DOL_URL_ROOT."/societe/card.php?socid=".$societe->id);

						// $nexp = $project->array_options ["options_numexp"];
						// $this->info_box_contents[$i][3] = array('td' => 'align="left"',
						// 	'text' => $nexp);

						// $datep = $project->array_options ["options_datep"];
						// $this->info_box_contents[$i][4] = array('td' => 'align="left"',
						// 	'text' => $datep);

						$this->info_box_contents[$i][3] = array('td' => 'align="left" class="'.$oddeven1.'"',
							'text' =>  dol_print_date($colabor->datec, 'day', ''));

						$user->fetch($colabor->fk_user_create);
						$this->info_box_contents[$i][4] = array('td' => 'align="left" class="'.$oddeven1.'"',
							'text' => '',
							'url' => $user->getNomUrl(-1, '', 0, 0, 16, 0, 'firstelselast', '').'<a');

						$this->info_box_contents[$i][5] = array('td' => 'align="left" class="'.$oddeven1.'"',
							'text' => img_edit_add(),
							'url' => DOL_URL_ROOT."/projet/card.php?id=".$project->id."&amp;action=insert_colabor");
							// 'url' => $_SERVER["PHP_SELF"]."?id=".$object->id."&amp;action=insert_colabor");


						$this->info_box_contents[$i][6] = array('td' => 'align="left" class="'.$oddeven1.'"',
							'text' => img_edit(),
							'url' => DOL_URL_ROOT."/projet/card.php?id=".$project->id);
							
					// }
					$i++;
					
				}
			}
		}
	}

    /**
     * Method to show box. Called by Dolibarr eatch time it wants to display the box.
     *
     * @param array $head       Array with properties of box title
     * @param array $contents   Array with properties of box lines
     * @param int   $nooutput   No print, only return string
     * @return void
     */
    public function showBox($head = null, $contents = null, $nooutput = 0)
    {
        // You may make your own code here…
        // … or use the parent's class function using the provided head and contents templates
        parent::showBox($this->info_box_head, $this->info_box_contents);
    }
}
