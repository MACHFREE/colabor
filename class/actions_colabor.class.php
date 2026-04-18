<?php
/* Copyright (C) ---Put here your own copyright and developer email---
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
 * \file    htdocs/modulebuilder/template/class/actions_mymodule.class.php
 * \ingroup mymodule
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */

/**
 * Class ActionsMyModule
 */

include_once DOL_DOCUMENT_ROOT.'/core/class/commoninvoice.class.php';
include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobjectline.class.php';
include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
dol_include_once('/colabor/class/colabor.class.php');


class ActionsColabor
{

   

    /**
     * @var DoliDB Database handler.
     */
    public $db;
    public $langs;

    /**
     * @var string Error code (or message)
     */
    public $error = '';


    /**
     * @var array Errors
     */
    public $errors = array();


    /**
     * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
     */
    public $results = array();

    /**
     * @var string String displayed by executeHook() immediately after return
     */
    public $resprints;


    //$hooks => array('projectcard','invoicecard','productcard','thirdpartycard','servicecard','ordercard','propalcard','printFieldListWhere');
   

    /**
     * Constructor
     *
     *  @param      DoliDB      $db      Database handler
     */
    public function __construct($db)
    {
        global $conf;

       

        $this->db = $db;
                 
        $thirparties = $conf->global->colabor_Thirparties;
        $products = $conf->global->colabor_Products;
        $orders = $conf->global->colabor_Orders;
        $proposals = $conf->global->colabor_Proposals;
        $bills = $conf->global->colabor_Bills;
        $project = $conf->global->colabor_Project;
        $sertega = $conf->global->colabor_Sertega;
        $preopportunity = $conf->global->colabor_Preopportunity;
        $waranti = $conf->global->colabor_Waranti;

        if($thirparties == 1){
            $p1_1     =   'thirdpartycard';
            $p1_2     =   'thirdpartysupplier';
            $p1_3     =   'thirdpartycomm';
        }

        if($products == 1){
            $p2     =   'productcard';
        } 

        if($proposals == 1){
            $p3_1   =   'propalcard';
            $p3_2   =   'supplier_proposalcard';
        }

        if($orders == 1){
            $p4_1   =   'ordercard';
            $p4_2   =   'ordersuppliercard';
        }

        if($bills == 1 ){
            $p5_1     =   'invoicecard';
            $p5_2     =   'invoicesuppliercard';    
        }
        if($project == 1){
            $p6     = 'projectcard';
        }
        if($sertega == 1){
            $p7     = 'sertegacard';
        }

        if($preopportunity == 1){
            $p8     = 'preopportunitycard';
        }

        if($waranti == 1){
            $p9     = 'waranticard';
        }


       $this->colaborVariables = array($p1_1,$p1_2,$p1_3, $p2, $p3_1, $p3_2, $p4_1, $p4_2, $p5_1, $p5_2, $p6, $p7, $p8, $p9);
                       
        
    }


    /**
     * Execute action
     *
     * @param   array           $parameters     Array of parameters
     * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          $action         'add', 'update', 'view'
     * @return  int                             <0 if KO,
     *                                          =0 if OK but we want to process standard actions too,
     *                                          >0 if OK and we want to replace standard actions.
     */
    public function getNomUrl($parameters, &$object, &$action)
    {
        global $db,$langs,$conf,$user;
        $this->resprints = '';
        return 0;
    }

    /**
     * Overloading the doActions function : replacing the parent's function with the one below
     *
     * @param   array           $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          $action         Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    public function doActions($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $user, $langs,$db;
       

        $error = 0; // Error counter

        

        // $colabor= array('projectcard','invoicecard','productservicelist');
        /* print_r($parameters); print_r($object); echo "action: " . $action; */
        if (in_array($parameters['currentcontext'], $this->colaborVariables))      // do something only for the context 'somecontext2'
        {
           
            //registro de lineas
            if ($action == "insert" && ! GETPOST('cancel') ) {
                  $object->id = $id = (GETPOST('socid', 'int')>0? GETPOST('socid', 'int') : (GETPOST('id', 'int')>0?GETPOST('id', 'int'):GETPOST('facid', 'int')));

                  if (GETPOST("description", 'none')){
                    $mentionsJson = $this->parseMentionsFromText(GETPOST('description', 'none'));

                    $sql ="INSERT INTO ".MAIN_DB_PREFIX."colabor (fk_user_create,";
                    $sql.= " fk_element, fk_ref, elementtype, description, datec, entity, mentions,annotation_type)";
                    $sql.= " VALUES (";
                    $sql.= "".$user->id;
                    $sql.= ", ".$object->id;
                    $sql.= ",'".$object->ref."'";
                    $sql.= ",'".$object->element."'";
                    $sql.= ",'".$db->escape(GETPOST('description', 'none'))."'";
                    $sql.= ",'".$db->idate(dol_now())."'";
                    $sql.= ", ".$conf->entity;
                    $sql.= ", ".($mentionsJson !== null ? "'".$db->escape($mentionsJson)."'" : "NULL");
                    $sql.= ",'".$db->escape(GETPOST('annotation_type', 'none'))."'";
                    $sql.= ") ";


                   // var_dump($db->escape(GETPOST('description', 'none')));

                    dol_syslog(get_class($this).'::create sql='.$sql);
                    if ($db->query($sql)){

                         require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
                        $now=dol_now();
                        $actioncomm = new ActionComm($this->db);
                        $actioncomm->type_code   = 'COLAB'; // Type of event ('AC_OTH', 'AC_OTH_AUTO', 'AC_XXX'...)
                        $actioncomm->code        = 'COLAB';
                        $actioncomm->label       = 'ANOTACIÓN: '.GETPOST("description") ;
                        $actioncomm->note        =  GETPOST("description") ;
                        $actioncomm->datep       = $now;
                        $actioncomm->datef       = $now;
                        $actioncomm->percentage  = 100; // Not applicable
                        $actioncomm->authorid    = $user->id; // User saving action
                        $actioncomm->userownerid = $user->id; // Owner of action
                      
                        $actioncomm->socid       = $object->id;
                        
                        $actioncomm->contact_id  = $object->contact_id;


                        $actioncomm->fk_element  = $object->id;

                        $actioncomm->elementtype = $object->element;
                        
                        $ret = $actioncomm->create($user); // User creating action


                        setEventMessages($langs->trans("ColaborCreate"), null, 'mesgs');
                       // header("Location: ".$_SERVER['PHP_SELF'].'?id='.$object->id);
                      // exit;
                    }else{
                        setEventMessages($langs->trans("ErrorCreateColabor")."<br>".$sql, null, 'errors');
                    }
                 }else{
                    setEventMessages($langs->trans("Colaborerror"), null, 'errors');  
                }
            }

            //edicion de lineas
            if ($action == "edit_C" && ! GETPOST('cancel')) {
                if (GETPOST("description", 'none')){
                    $mentionsJson = $this->parseMentionsFromText(GETPOST('description', 'none'));

                    $sql = "UPDATE ".MAIN_DB_PREFIX."colabor";
                    $sql.= " SET descriptionedit='".$db->escape(GETPOST('description', 'none'))."'";
                    $sql.= ", fk_user_modif=".$user->id;
                    $sql.= ", datee='".$db->idate(dol_now())."'";
                    $sql.= ", mentions=".($mentionsJson !== null ? "'".$db->escape($mentionsJson)."'" : "NULL");
                    $sql.= ", annotation_type='".$db->escape(GETPOST('annotation_type', 'none'))."'";
                    $sql.= " WHERE rowid =".GETPOST("cid");

                    dol_syslog(get_class($this).'::update sql='.$sql);
                    if ($db->query($sql))
                        setEventMessages($langs->trans("ColaborUpdate"), null, 'mesgs');
                    else
                        setEventMessages($langs->trans("ErrorCreateColabor")."<br>".$sql, null, 'errors');

                }else{
                    setEventMessages($langs->trans("Colaborerror"), null, 'errors');  
                }
            }

            if( $action == "confirm_deletelinec"){
                if (GETPOST("cid")){

                    $sql = "DELETE";
                    $sql .= " FROM ".MAIN_DB_PREFIX."colabor";
                    $sql .= " WHERE rowid = ".GETPOST("cid");
                  
                    dol_syslog(get_class($this).'::delete sql='.$sql);
                    if ($db->query($sql))
                        setEventMessages($langs->trans("ColaborDelete"), null, 'mesgs');
                    else
                        setEventMessages($langs->trans("ErrorDeleteColabor")."<br>".$sql, null, 'errors');

                }else{
                    setEventMessages($langs->trans("Colaborerror"), null, 'errors');  
                }
            }
       
        }

        if (! $error) {
            $this->results = array('myreturn' => 999);
            $this->resprints = 'A text to show';
            return 0; // or return 1 to replace standard code
        } else {
            $this->errors[] = 'Error message';
            return -1;
        }
    }


    /**
     * Overloading the doActions function : replacing the parent's function with the one below
     *
     * @param   array           $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          $action         Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    public function doMassActions($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $user, $langs;

        $error = 0; // Error counter


        /* print_r($parameters); print_r($object); echo "action: " . $action; */
        if (in_array($parameters['currentcontext'], $this->colaborVariables))      // do something only for the context 'somecontext1' or 'somecontext2'
        {

        }

        if (! $error) {
            $this->results = array('myreturn' => 999);
            $this->resprints = 'A text to show';
            return 0; // or return 1 to replace standard code
        } else {
            $this->errors[] = 'Error message';
            return -1;
        }
    }


    /**
     * Overloading the addMoreMassActions function : replacing the parent's function with the one below
     *
     * @param   array           $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          $action         Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    public function addMoreMassActions($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $user, $langs;

        $error = 0; // Error counter

        /* print_r($parameters); print_r($object); echo "action: " . $action; */
        if (in_array($parameters['currentcontext'], $this->colaborVariables))       // do something only for the context 'somecontext1' or 'somecontext2'
        {


        }

        if (! $error) {
            return 0; // or return 1 to replace standard code
        } else {
            $this->errors[] = 'Error message';
            return -1;
        }
    }



    /**
     * Execute action
     *
     * @param   array   $parameters     Array of parameters
     * @param   Object  $object         Object output on PDF
     * @param   string  $action         'add', 'update', 'view'
     * @return  int                     <0 if KO,
     *                                  =0 if OK but we want to process standard actions too,
     *                                  >0 if OK and we want to replace standard actions.
     */
    public function beforePDFCreation($parameters, &$object, &$action)
    {
        global $conf, $user, $langs;
        global $hookmanager;

        $outputlangs=$langs;

        $ret=0; $deltemp=array();
        dol_syslog(get_class($this).'::executeHooks action='.$action);

        /* print_r($parameters); print_r($object); echo "action: " . $action; */
        if (in_array($parameters['currentcontext'], array('somecontext1','somecontext2')))      // do something only for the context 'somecontext1' or 'somecontext2'
        {
        }

        return $ret;
    }

    /**
     * Execute action
     *
     * @param   array   $parameters     Array of parameters
     * @param   Object  $pdfhandler     PDF builder handler
     * @param   string  $action         'add', 'update', 'view'
     * @return  int                     <0 if KO,
     *                                  =0 if OK but we want to process standard actions too,
     *                                  >0 if OK and we want to replace standard actions.
     */
    public function afterPDFCreation($parameters, &$pdfhandler, &$action)
    {
        global $conf, $user, $langs;
        global $hookmanager;

        $outputlangs=$langs;

        $ret=0; $deltemp=array();
        dol_syslog(get_class($this).'::executeHooks action='.$action);

        /* print_r($parameters); print_r($object); echo "action: " . $action; */
        if (in_array($parameters['currentcontext'], array('somecontext1','somecontext2')))      // do something only for the context 'somecontext1' or 'somecontext2'
        {
        }

        return $ret;
    }

    /* Add here any other hooked methods... */


    /**
     * Overloading the addMoreMassActions function : replacing the parent's function with the one below
     *
     * @param   array           $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          $action         Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    public function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $user, $langs;
        $langs->load('colabor@colabor');

        $error = 0; // Error counter
        

        if (in_array($parameters['currentcontext'], $this->colaborVariables))      // do something only for the context 'somecontext1' or 'somecontext2'
        {
            
            if ($object->statut != 0 || $action != 'edit' && $object->element == 'project' || $object->element == 'product'|| $object->element == 'societe' || $object->element == 'sertega'  || $object->element == 'waranti')
            {

                $reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action);
            }
             
            switch ($object->element) {
                case 'societe':
                    $Did = 'socid';
                    break;
                case 'facture':
                    $Did = 'facid';
                    break;
                default:
                   $Did = 'id';
                    break;
            }

           

    // }



    // public function formAddObjectLine($parameters, &$object, &$action, $hookmanager)
    // {

       

        global $conf, $user, $langs,$db;
        $langs->load('colabor@colabor');

        dol_include_once('/custom/colabor/class/colabor.class.php');

        $colabor = new colabor($this->db);
        $userstatic = new User($db);
        $form = new Form($db);

        switch ($object->element) {
            case 'societe':
                $Did = 'socid';
                $id  = GETPOST('socid', 'int')?GETPOST('socid', 'int'):GETPOST('id', 'int');
                break;
            case 'facture':
                $Did = 'facid';
                 $id  = GETPOST('facid', 'int')?GETPOST('facid', 'int'):GETPOST('id', 'int');
                break;
            default:
               $Did = 'id';
                $id  = GETPOST('id', 'int');
                break;
        }
        $ref = GETPOST('ref', 'alpha');

        $error = 0; // Error counter

        /* print_r($parameters); print_r($object); echo "action: " . $action; */
        // if (in_array($parameters['currentcontext'], $this->colaborVariables) && $user->rights->colabor->read)      // do something only for the context 'somecontext1' or 'somecontext2'
        // {
            
            if ($id || $ref) {
                

                $limit = GETPOST("colabor_limit", "int") ? GETPOST("colabor_limit", "int") : 5;
                $page = GETPOST("colabor_page", "int") ? GETPOST("colabor_page", "int") : 0;
                $offset = $page * $limit;

                $sql = "SELECT rowid id, ref, fk_user_create, fk_user_modif, fk_element, fk_ref, elementtype,";
                $sql .= " description, descriptionedit, datec, datee, state, entity, mentions, annotation_type";
                $sql .= " FROM ".MAIN_DB_PREFIX."colabor";

                if (!empty($id)) {
                    $sql .= " WHERE elementtype='".$object->element."' and fk_element=".$id;
                    $sql .= " AND state = 1 ORDER BY datec DESC";
                } elseif (!empty($ref)) {
                    $sql .= " WHERE fk_ref='".$this->db->escape($ref)."'";
                    $sql .= " AND state = 1 ORDER BY datec DESC";
                }

                // Query con LIMIT para datos
                $sqlPage = $sql." LIMIT ".$offset.",".$limit;
                $resql = $this->db->query($sqlPage);
                $num = $this->db->num_rows($resql);

                // Query para total
                $sqlTotal = preg_replace('/ORDER BY.*$/', '', $sql);
                $resqlTotal = $this->db->query($sqlTotal);
                $nbtotalofrecords = $this->db->num_rows($resqlTotal);

                if ($nbtotalofrecords > 0) {
                    //print load_fiche_titre($langs->trans("ColaborHistor"), '');

                   

                    
                    // Header con selector y paginador
                    print '<div style="display: flex; justify-content: flex-end; align-items: center; gap: 15px; margin-bottom: 15px;">';
                    
                    print_barre_liste($langs->trans("ColaborHistor"), $page, $_SERVER["PHP_SELF"], '', '', '', '', $num, $nbtotalofrecords, 'order','','',' left','','',1);

                    // Selector de registros por página
                    print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'" style="display: inline;">';
                    print '<input type="hidden" name="'.$Did.'" value="'.$object->id.'">';
                    print '<select name="colabor_limit" id="colabor_limit" onchange="this.form.submit();" style="padding: 5px 10px;">';
                    foreach(array(5, 10, 20, 40, 80, 100) as $val) {
                        print '<option value="'.$val.'"'.($limit == $val ? ' selected' : '').'>'.$val.'</option>';
                    }
                    print '</select>';
                    print '</form>';
                    
                    // Contador
                    //print '<span style="color: #666;">'.$num.' / '.$nbtotalofrecords.'</span>';
                    
                    // Paginación
                    if ($nbtotalofrecords > $limit) {
                        $nbpages = ceil($nbtotalofrecords / $limit);
                        
                        // Flecha anterior
                        if ($page > 0) {
                            print '<a href="'.$_SERVER["PHP_SELF"].'?'.$Did.'='.$object->id.'&colabor_page='.($page - 1).'&colabor_limit='.$limit.'">‹</a>';
                        } else {
                            print '<span style="color: #ccc;">‹</span>';
                        }
                        
                        // Números de páginacenter
                        for ($p = 0; $p < $nbpages; $p++) {
                            if ($p == $page) {
                                print '<strong>['.($p + 1).']</strong>';
                            } else {
                                print '<a href="'.$_SERVER["PHP_SELF"].'?'.$Did.'='.$object->id.'&colabor_page='.$p.'&colabor_limit='.$limit.'">['.($p + 1).']</a>';
                            }
                        }
                        
                        // Flecha siguiente
                        if ($page < $nbpages - 1) {
                            print '<a href="'.$_SERVER["PHP_SELF"].'?'.$Did.'='.$object->id.'&colabor_page='.($page + 1).'&colabor_limit='.$limit.'">›</a>';
                        } else {
                            print '<span style="color: #ccc;">›</span>';
                        }
                    }
                    
                    print '</div>';
                    
                    // Estilos inline para tooltip de edición (no depende del cache del CSS)
                    print '<style>
                    .colabor-edit-wrap{position:relative;display:inline-block;vertical-align:middle}
                    .colabor-tip-trigger{cursor:help;color:#aaa;font-size:13px;transition:color .15s}
                    .colabor-tip-trigger:hover{color:#25A580}
                    .colabor-tip-box{display:none;position:absolute;top:1.6em;left:0;z-index:10500;background:#fff;border:1px solid #d0d5dd;border-radius:6px;box-shadow:0 6px 20px rgba(0,0,0,.14);max-width:440px;min-width:240px;font-size:13px;line-height:1.55;overflow:hidden}
                    .colabor-edit-wrap:hover .colabor-tip-box{display:block}
                    .colabor-tip-meta{display:flex;justify-content:space-between;align-items:center;padding:7px 12px 6px;background:#f5f7f9;border-bottom:1px solid #e8ecf0;gap:10px}
                    .colabor-tip-label{font-size:10px;font-weight:700;color:#666;text-transform:uppercase;letter-spacing:.06em;white-space:nowrap}
                    .colabor-tip-date{font-size:11px;color:#999;font-style:italic;white-space:nowrap}
                    .colabor-tip-content{padding:10px 12px;color:#333;word-wrap:break-word;max-height:260px;overflow-y:auto}
                    </style>';

                    // Tabla
                    print '<div class="div-table-responsive">';
                    print '<table class="centpercent noborder">';
                    print '<tr class="liste_titre">';
                    print '<th class="liste_titre center" style="width: 120px;">'.$langs->trans("By").'</th>';
                    print '<th class="liste_titre center" style="width: 100px;">'.$langs->trans("Date").'</th>';
                    print '<th class="liste_titre center">'.$langs->trans("TypeAnnotation").'</th>';
                    print '<th class="liste_titre center">'.$langs->trans("Label").'</th>';
                    print '<th class="liste_titre center" style="width: 80px;">Acciones</th>';
                    print '</tr>';

                    $i = 0;
                    while($i < $num) {
                        $colabor = $this->db->fetch_object($resql);
                        
                        $hasEdit = !empty($colabor->descriptionedit);
                        $displayText = $hasEdit ? $colabor->descriptionedit : $colabor->description;
                        
                        print '<tr class="oddeven">';
                        
                        // Usuario
                        $userstatic->fetch($colabor->fk_user_create);
                        print '<td class="center">'.$userstatic->getNomUrl(-1, '', 0, 0, 16, 0, 'firstelselast', '').'</td>';
                        
                        // Fecha
                        print '<td class="center">'.dol_print_date($colabor->datec, 'day', '').'</td>';

                        //TIPO DE ANOTACION
                        dol_include_once('/custom/colabor/class/colabor.class.php');

                        $colabor1 = new colabor($this->db);

                        $anotacion = $colabor1->get_tipo_anotaciones($colabor->annotation_type,1);
                        print '<td class="center">'.$anotacion.'</td>';
                        
                        // Descripción con tooltip
                        print '<td class="left">';
                        $renderedText = $this->renderTextWithMentions($displayText);
                        if ($hasEdit) {
                            $renderedOriginal = $this->renderTextWithMentions($colabor->description);
                            $editDate = dol_print_date($colabor->datee, 'dayhour', '');

                            print '<div class="colabor-edit-wrap">';
                            print '<span class="colabor-tip-trigger"><i class="fas fa-edit"></i></span>';
                            print '<div class="colabor-tip-box">';
                            print '<div class="colabor-tip-meta">';
                            print '<span class="colabor-tip-label">Texto anterior</span>';
                            print '<span class="colabor-tip-date">'.$editDate.'</span>';
                            print '</div>';
                            print '<div class="colabor-tip-content">'.$renderedOriginal.'</div>';
                            print '</div>';
                            print '</div>';
                            print ' '.$renderedText;
                        } else {
                            print $renderedText;
                        }
                        // Fallback: badges del JSON para anotaciones antiguas sin menciones en texto
                        $hasMentionsInText = preg_match('/@\[/', $displayText) || preg_match('/class="mention"/', $displayText);
                        if (!$hasMentionsInText && !empty($colabor->mentions)) {
                            print $this->renderMentionsDisplay($colabor->mentions);
                        }
                        print '</td>';
                        
                        // Acciones
                        print '<td class="center">';
                        
                        if ($user->rights->colabor->edit) {
                            print '<a class="btn-edit" href="'.$_SERVER["PHP_SELF"].'?'.$Did.'='.$object->id.'&action=edit_colabor&cid='.$colabor->id.'&token='.newToken().'" title="'.$langs->trans('Edit').'">';
                            print img_edit();
                            print '</a> ';
                        }
                        
                        if ($user->rights->colabor->delete || $colabor->fk_user_create == $user->id) {
                            print '<a class="btn-delete" href="'.$_SERVER["PHP_SELF"].'?'.$Did.'='.$object->id.'&action=delete_colabor&cid='.$colabor->id.'&token='.newToken().'" title="'.$langs->trans('Delete').'">';
                            print img_delete();
                            print '</a>';
                        }
                        
                        print '</td>';
                        print '</tr>';
                        
                        $i++;
                    }
                    print '</table>';
                    print '</div>';
                }
                
            }


            if ($action == 'insert_colabor'  )
            {
               
                dol_include_once('/custom/colabor/class/colabor.class.php');

                $colabor = new colabor($this->db);

                print '<!-- Edit annotation -->'."\n";
                print '<form action="'.$_SERVER["PHP_SELF"].'?'.$Did.'='.$object->id.'" method="POST">';
                print '<input type="hidden" name="token" value="'.newToken().'">';
                print '<input type="hidden" name="action" value="insert">';

                // Contenedor centrado
                print '<div class="center" style="max-width: 900px; margin: 20px auto;">';

                print load_fiche_titre($langs->trans("InsertNewPerformance"), null, null);

                print '<div class="left" style="margin-top: 15px;">';

                $optionsArray2 = $colabor->get_tipo_anotaciones('');
                print '<label for="socid">'.$langs->trans("TypeAnnotation").': </label>';
                print $form->selectarray('annotation_type', $optionsArray2,GETPOST('annotation_type'),  0, 0, '','','500');

                print '</div><br>';

                require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
                $doleditor = new DolEditor('description', $value, '', 130, 'description', 'In', false, false, true, ROWS_5, '50%');
                print $this->renderMentionsFormSection($doleditor->Create(1), $object);

                // Botones centrados
                print '<div class="center" style="margin-top: 15px;">';
                print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
                print '&nbsp;&nbsp;';
                print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
                print '</div>';

                print '</div>';
                print '</form>';
                            }


            //formulario de edicion
            if ($action == 'edit_colabor'  )
            {

                $sql  = "SELECT description, descriptionedit, mentions, annotation_type";
                $sql .= " FROM ".MAIN_DB_PREFIX."colabor";
                $sql .= " WHERE state = 1 AND rowid=".GETPOST("cid", "int");
                $row = $db->query($sql);
                $obj = $db->fetch_object($row);

                print '<!-- Edit annotation -->'."\n";
                print '<form action="'.$_SERVER["PHP_SELF"].'?'.$Did.'='.$object->id.'" method="POST">';
                print '<input type="hidden" name="token" value="'.newToken().'">';
                print '<input type="hidden" name="cid" value="'.GETPOST("cid").'">';
                print '<input type="hidden" name="action" value="edit_C">';

                // Contenedor centrado
                print '<div class="center" style="max-width: 900px; margin: 20px auto;">';
                print load_fiche_titre($langs->trans("EditPerformance"), null, null);
                $desc = $obj->descriptionedit == null ? $obj->description : $obj->descriptionedit;

                 dol_include_once('/custom/colabor/class/colabor.class.php');

                $colabor = new colabor($this->db);

                $optionsArray2 = $colabor->get_tipo_anotaciones('');
                print '<div class="left" style="margin-top: 15px;">';
                print '<label for="socid">'.$langs->trans("TypeAnnotation").': </label>';
                print $form->selectarray('annotation_type', $optionsArray2,GETPOST('annotation_type')?GETPOST('annotation_type'):$obj->annotation_type ,  0, 0, '','','500');

                print '</div><br>';


                require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
                $doleditor = new DolEditor('description', $desc, '', 130, 'description', 'In', false, false, true, ROWS_5, '50%');
                print $this->renderMentionsFormSection($doleditor->Create(1), $object);

                // Botones centrados
                print '<div class="center" style="margin-top: 15px;">';
                print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
                print '&nbsp;&nbsp;';
                print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
                print '</div>';

                print '</div>';
                print '</form>';
            }

            //ventana de confirmacion para eliminar linea

            if ($action == 'delete_colabor')
            {
                $formconfirm=$form->formconfirm($_SERVER["PHP_SELF"].'?'.$Did.'='.$object->id.'&cid='.GETPOST("cid"), $langs->trans('DeleteHistorytLine'), $langs->trans('ConfirmDeleteHistoryLine'), 'confirm_deletelinec', '', 0, 1);
                print $formconfirm;
            }

            if($action != 'edit_colabor' && $action != 'insert_colabor'  && $action != 'edit'){
                //boton de ingresar nuevo registro
                print '<div class="inline-block divButAction">';
                print '<a class="butAction" id="buttoncola"  href="'.$_SERVER['PHP_SELF'].'?'.$Did.'='.$object->id.'&amp;action=insert_colabor&token='.newToken().'" id="colabor" >';
                print $langs->trans('NewPerformance') ;
                print '</a></div>';
            }
            

        }

        if (! $error) {
            return 0; // or return 1 to replace standard code
        } else {
            $this->errors[] = 'Error message';
            return -1;
        }
    }


    public function formObjectOptions($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $user, $langs,$db;
        $langs->load('colabor@colabor');

        dol_include_once('/colabor/class/colabor.class.php');

        $colabor = new colabor($db);
        $userstatic = new User($db);
        $form = new Form($db);


        $error = 0; // Error counter


        /* print_r($parameters); print_r($object); echo "action: " . $action; */
        if (in_array($parameters['currentcontext'], $this->colaborVariables))      // do something only for the context 'somecontext1' or 'somecontext2'
        {

        }

        if (! $error) {
            return 0; // or return 1 to replace standard code
        } else {
            $this->errors[] = 'Error message';
            return -1;
        }
    }

    // =========================================================================
    // MENTIONS HELPERS
    // =========================================================================

    /**
     * Wrap the editor HTML in the mentions container.
     * Adds the dropdown overlay and the read-only badges row.
     * Loads mentions.js and initialises ColaborMentions.
     *
     * @param string      $editorHtml  HTML returned by DolEditor::Create(1)
     * @param object|null $object      Current Dolibarr object (for socid context)
     * @return string  HTML to print
     */
    private function renderMentionsFormSection($editorHtml, $object = null)
    {
        $ajaxUrl = DOL_URL_ROOT.'/custom/colabor/ajax/mentions_search.php';

        // Extract socid: for societe cards $object->id IS the socid; for other elements use $object->socid
        $socid = 0;
        if ($object) {
            if (isset($object->element) && $object->element === 'societe') {
                $socid = (int)$object->id;
            } elseif (!empty($object->socid)) {
                $socid = (int)$object->socid;
            }
        }

        // Wrap editor + dropdown in a relatively-positioned container
        $html  = '<div id="colabor-editor-container" style="position:relative;">';
        $html .= $editorHtml;
        $html .= '<div id="colabor-mention-dropdown"></div>';
        $html .= '</div>';

        // Badge container oculto (los tokens ya son visibles dentro del editor)
        $html .= '<div id="colabor-mentions-badges" style="display:none"></div>';

        // Assets
        $html .= '<link rel="stylesheet" href="'.DOL_URL_ROOT.'/custom/colabor/css/colabor.css?v=4">';
        $html .= '<script src="'.DOL_URL_ROOT.'/custom/colabor/js/mentions.js?v=4.17"></script>';
        $html .= '<script>';
        $html .= '$(document).ready(function(){';
        $html .= '  ColaborMentions.init({ajaxUrl:'.json_encode($ajaxUrl, JSON_HEX_TAG | JSON_HEX_AMP).', socid:'.(int)$socid.'});';
        $html .= '});';
        $html .= '</script>';

        return $html;
    }

    /**
     * Replace mention markers in stored text with clickable links for history display.
     * Handles two formats:
     *   - CKEditor: <span class="mention" data-type="X" data-id="Y">@Name</span>
     *   - Textarea:  @[Name|type:id]
     *
     * @param string $text  HTML or plain text from description / descriptionedit
     * @return string  Text with mention markers replaced by <a> links
     */
    private function renderTextWithMentions($text)
    {
        if (empty($text)) return $text;

        // Shared renderer — used by both CKEditor-span and @[...] callbacks
        $renderMention = function ($type, $id, $name) {
            $id  = (int)$id;
            $url = $this->getMentionUrl($type, $id);
            $css = ($type === 'user' || $type === 'contact')
                ? 'colabor-mention-link is-user'
                : 'colabor-mention-link';

            switch ($type) {
                case 'user':
                    $obj = new User($this->db);
                    $obj->fetch($id);
                    return $obj->getNomUrl(1, '', 0, 0, 0, 0, 'firstelselast', '');
                case 'contact':
                    dol_include_once('/contact/class/contact.class.php');
                    $obj = new Contact($this->db);
                    $obj->fetch($id);
                    return $obj->getNomUrl(1);
                case 'propal':
                    dol_include_once('/comm/propal/class/propal.class.php');
                    $obj = new Propal($this->db);
                    $obj->fetch($id);
                    return $obj->getNomUrl(1);
                case 'commande':
                    dol_include_once('/commande/class/commande.class.php');
                    $obj = new Commande($this->db);
                    $obj->fetch($id);
                    return $obj->getNomUrl(1);
                case 'facture':
                    dol_include_once('/compta/facture/class/facture.class.php');
                    $obj = new Facture($this->db);
                    $obj->fetch($id);
                    return $obj->getNomUrl(1);
                case 'project':
                    dol_include_once('/projet/class/project.class.php');
                    $obj = new Project($this->db);
                    $obj->fetch($id);
                    return $obj->getNomUrl(1);
                case 'product':
                    dol_include_once('/product/class/product.class.php');
                    $obj = new Product($this->db);
                    $obj->fetch($id);
                    return $obj->getNomUrl(1);
                case 'service':
                    dol_include_once('/product/class/product.class.php');
                    $obj = new Product($this->db);
                    $obj->fetch($id);
                    return $obj->getNomUrl(1);
                case 'branch':
                    dol_include_once('/societe/class/societe.class.php');
                    $obj = new Societe($this->db);
                    $obj->fetch($id);
                    return $obj->getNomUrl(1);
                default:
                    $icon = '&#128196;';
                    return '<a href="'.$url.'" class="'.$css.'">'.$icon.' '.$name.'</a>';
            }
        };

        // ── Formato CKEditor: <span class="mention" ...> — acepta cualquier orden de atributos
        $text = preg_replace_callback(
            '/<span([^>]*class="mention"[^>]*)>@?([^<]*)<\/span>/i',
            function ($m) use ($renderMention) {
                $attrs = $m[1];
                preg_match('/data-type="([^"]+)"/', $attrs, $tm);
                preg_match('/data-id="(\d+)"/',     $attrs, $im);
                $type = isset($tm[1]) ? $tm[1] : '';
                $id   = isset($im[1]) ? (int)$im[1] : 0;
                $name = htmlspecialchars(strip_tags(trim($m[2])), ENT_QUOTES, 'UTF-8');
                if (!$type || !$id) return $name;
                return $renderMention($type, $id, $name);
            },
            $text
        );

        // ── Formato textarea: @[Name|type:id]
        $text = preg_replace_callback(
            '/@\[([^\|\]]+)\|([a-z_]+):(\d+)\]/',
            function ($m) use ($renderMention) {
                $name = htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8');
                $type = $m[2];
                $id   = (int)$m[3];
                return $renderMention($type, $id, $name);
            },
            $text
        );

        return $text;
    }

    /**
     * Extract mentions from POST description and return mentions JSON.
     * Handles two formats:
     *   - CKEditor HTML: <span class="mention" data-type="X" data-id="Y">@Name</span>
     *   - Textarea text: @[Name|type:id]
     * Called on save (insert / edit) to populate the mentions column.
     *
     * @param string $text  Raw POST description value (HTML or plain text)
     * @return string|null  JSON string or null if no mentions found
     */
    private function parseMentionsFromText($text)
    {
        if (empty($text)) return null;

        $validDocTypes = [
            'propal', 'commande', 'facture', 'project',
            'supplier_proposal', 'order_supplier', 'invoice_supplier',
        ];
        $docLabels = [
            'propal'            => 'Presupuesto',
            'commande'          => 'Pedido',
            'facture'           => 'Factura',
            'project'           => 'Proyecto',
            'supplier_proposal' => 'Presupuesto proveedor',
            'order_supplier'    => 'Pedido proveedor',
            'invoice_supplier'  => 'Factura proveedor',
        ];

        $out  = ['users' => [], 'contacts' => [], 'documents' => [], 'products' => [], 'services' => [], 'branches' => []];
        $seen = [];

        // ── Formato CKEditor: <span class="mention" data-type="X" data-id="Y">
        preg_match_all(
            '/<span\s+class="mention"\s+data-type="([^"]+)"\s+data-id="(\d+)">@?([^<]*)<\/span>/i',
            $text,
            $spanMatches,
            PREG_SET_ORDER
        );
        foreach ($spanMatches as $m) {
            $type = $m[1];
            $id   = (int)$m[2];
            $name = strip_tags(trim($m[3]));
            $this->_addMentionToOut($out, $seen, $type, $id, $name, $validDocTypes, $docLabels);
        }

        // ── Formato textarea: @[Name|type:id]
        preg_match_all('/@\[([^\|\]]+)\|([a-z_]+):(\d+)\]/', $text, $textMatches, PREG_SET_ORDER);
        foreach ($textMatches as $m) {
            $name = strip_tags(trim($m[1]));
            $type = $m[2];
            $id   = (int)$m[3];
            $this->_addMentionToOut($out, $seen, $type, $id, $name, $validDocTypes, $docLabels);
        }

        if (empty($out['users']) && empty($out['contacts']) && empty($out['documents'])
            && empty($out['products']) && empty($out['services']) && empty($out['branches'])) {
            return null;
        }

        return json_encode($out);
    }

    /**
     * Helper: add a single mention entry to the output array (deduplicates).
     */
    private function _addMentionToOut(&$out, &$seen, $type, $id, $name, $validDocTypes, $docLabels)
    {
        $key = $type.':'.$id;
        if (isset($seen[$key])) return;
        $seen[$key] = true;

        if ($type === 'user') {
            $out['users'][] = ['id' => $id, 'name' => $name, 'login' => '', 'type' => 'user'];
        } elseif ($type === 'contact') {
            $out['contacts'][] = ['id' => $id, 'name' => $name, 'type' => 'contact', 'fk_soc' => 0];
        } elseif ($type === 'product') {
            $out['products'][] = ['id' => $id, 'ref' => $name, 'label' => 'Producto', 'type' => 'product'];
        } elseif ($type === 'service') {
            $out['services'][] = ['id' => $id, 'ref' => $name, 'label' => 'Servicio', 'type' => 'service'];
        } elseif ($type === 'branch') {
            $out['branches'][] = ['id' => $id, 'name' => $name, 'type' => 'branch'];
        } elseif (in_array($type, $validDocTypes)) {
            $out['documents'][] = [
                'id'    => $id,
                'ref'   => $name,
                'type'  => $type,
                'label' => isset($docLabels[$type]) ? $docLabels[$type] : $type,
            ];
        }
    }

    /**
     * Render mentions JSON column as badge links (fallback for old annotations).
     *
     * @param string $mentionsJson  JSON string from DB
     * @return string  HTML to print
     */
    private function renderMentionsDisplay($mentionsJson)
    {
        global $conf, $db;
        if (empty($mentionsJson)) return '';

        $mentions = json_decode($mentionsJson, true);
        if (empty($mentions) || !is_array($mentions)) return '';

        $hasAny = (!empty($mentions['users'])     && count($mentions['users'])     > 0)
               || (!empty($mentions['contacts'])  && count($mentions['contacts'])  > 0)
               || (!empty($mentions['documents']) && count($mentions['documents']) > 0)
               || (!empty($mentions['products'])  && count($mentions['products'])  > 0)
               || (!empty($mentions['services'])  && count($mentions['services'])  > 0)
               || (!empty($mentions['branches'])  && count($mentions['branches'])  > 0);

        if (!$hasAny) return '';

        $html  = '<div class="colabor-mentions-row">';
        $html .= '<span class="colabor-mentions-row-label">Menciones:</span>';

        if (!empty($mentions['users'])) {
            $userstat = new User($db);

            foreach ($mentions['users'] as $u) {
                $userstat->fetch($u['id']);
                $url  = $this->getMentionUrl('user', $u['id']);
                $name = htmlspecialchars($u['name'] ?: $u['login'], ENT_QUOTES, 'UTF-8');
                $html .= $userstat->getNomUrl(-1, '', 0, 0, 16, 0, 'firstelselast', '');//'<a href="'.$url.'" class="colabor-mention-link is-user">&#128100; '.$name.'</a>';
            }
        }

        if (!empty($mentions['contacts'])) {
            foreach ($mentions['contacts'] as $c) {
                $url  = $this->getMentionUrl('contact', $c['id']);
                $name = htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8');
                $html .= '<a href="'.$url.'" class="colabor-mention-link">&#128100; '.$name.'</a>';
            }
        }

        if (!empty($mentions['documents'])) {
            foreach ($mentions['documents'] as $d) {
                $url       = $this->getMentionUrl($d['type'], $d['id']);
                $ref       = htmlspecialchars($d['ref'],            ENT_QUOTES, 'UTF-8');
                $typeLabel = htmlspecialchars($d['label'] ?? $d['type'], ENT_QUOTES, 'UTF-8');
                $html .= '<a href="'.$url.'" class="colabor-mention-link" title="'.$typeLabel.'">&#128196; '.$ref.'</a>';
            }
        }

        if (!empty($mentions['products'])) {
            foreach ($mentions['products'] as $p) {
                $url = $this->getMentionUrl('product', $p['id']);
                $ref = htmlspecialchars($p['ref'], ENT_QUOTES, 'UTF-8');
                $html .= '<a href="'.$url.'" class="colabor-mention-link is-product" title="Producto">&#128717; '.$ref.'</a>';
            }
        }

        if (!empty($mentions['services'])) {
            foreach ($mentions['services'] as $s) {
                $url = $this->getMentionUrl('service', $s['id']);
                $ref = htmlspecialchars($s['ref'], ENT_QUOTES, 'UTF-8');
                $html .= '<a href="'.$url.'" class="colabor-mention-link is-service" title="Servicio">&#128295; '.$ref.'</a>';
            }
        }

        if (!empty($mentions['branches'])) {
            foreach ($mentions['branches'] as $b) {
                $url  = $this->getMentionUrl('branch', $b['id']);
                $name = htmlspecialchars($b['name'], ENT_QUOTES, 'UTF-8');
                $html .= '<a href="'.$url.'" class="colabor-mention-link is-branch" title="Sucursal">&#127970; '.$name.'</a>';
            }
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Convert stored @[Name|type:id] plain markers to <span class="mention"> HTML
     * so that CKEditor can display them as visual tokens when loading an existing annotation.
     *
     * @param string $text  Raw stored text
     * @return string  Text with @[...] replaced by <span class="mention">
     */
    private function _plainMentionsToHtml($text)
    {
        if (empty($text)) return $text;
        return preg_replace_callback(
            '/@\[([^\|\]]+)\|([a-z_]+):(\d+)\]/',
            function ($m) {
                $name = htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8');
                $type = $m[2];
                $id   = (int)$m[3];
                return '<span class="mention" data-type="'.$type.'" data-id="'.$id.'">@'.$name.'</span>';
            },
            $text
        );
    }

    /**
     * Returns the Dolibarr URL for a mention by type and ID.
     */
    private function getMentionUrl($type, $id)
    {
        $id = (int)$id;
        switch ($type) {
            case 'user':              return DOL_URL_ROOT.'/user/card.php?id='.$id;
            case 'contact':           return DOL_URL_ROOT.'/contact/card.php?id='.$id;
            case 'propal':            return DOL_URL_ROOT.'/comm/propal/card.php?id='.$id;
            case 'commande':          return DOL_URL_ROOT.'/commande/card.php?id='.$id;
            case 'facture':           return DOL_URL_ROOT.'/compta/facture/card.php?id='.$id;
            case 'project':           return DOL_URL_ROOT.'/projet/card.php?id='.$id;
            case 'supplier_proposal': return DOL_URL_ROOT.'/supplier_proposal/card.php?id='.$id;
            case 'order_supplier':    return DOL_URL_ROOT.'/fourn/commande/card.php?id='.$id;
            case 'invoice_supplier':  return DOL_URL_ROOT.'/fourn/facture/card.php?id='.$id;
            case 'product':           return DOL_URL_ROOT.'/product/card.php?id='.$id;
            case 'service':           return DOL_URL_ROOT.'/product/card.php?id='.$id;
            case 'branch':            return DOL_URL_ROOT.'/societe/card.php?socid='.$id;
            default:                  return '#';
        }
    }

}
