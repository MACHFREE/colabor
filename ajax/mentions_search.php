<?php
/**
 * AJAX endpoint: Mentions autocomplete for Colabor module
 * Returns JSON array of matching users, contacts and documents
 */
define('NOREQUIREMENU', 1);
define('NOCSRFCHECK', 1);

$res = 0;
if (!$res && file_exists("../../../../main.inc.php")) $res = @include "../../../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php"))    $res = @include "../../../main.inc.php";
if (!$res) die('Cannot load main.inc.php');

header('Content-Type: application/json; charset=utf-8');

if (empty($user->rights->colabor->read)) {
    echo json_encode(['error' => 'Acceso denegado']);
    exit;
}

$q         = trim(GETPOST('q', 'alpha'));
$emptyQ    = (strlen($q) === 0);
$results   = [];
$qEsc      = $db->escape($q);
$entity    = $conf->entity;
$socid     = (int)GETPOST('socid', 'int');   // 0 = sin filtro

// ── 1. USERS ─────────────────────────────────────────────────────────────────
// Empty query → show first active users as default list
// Non-empty query → search by login, firstname, lastname or full name
$sql  = "SELECT u.rowid AS id, u.login,";
$sql .= " TRIM(CONCAT(IFNULL(u.firstname,''), ' ', u.lastname)) AS fullname";
$sql .= " FROM " . MAIN_DB_PREFIX . "user u";
$sql .= " WHERE u.statut = 1";
if (!$emptyQ) {
    $sql .= "   AND (u.login LIKE '%" . $qEsc . "%'";
    $sql .= "     OR u.firstname LIKE '%" . $qEsc . "%'";
    $sql .= "     OR u.lastname  LIKE '%" . $qEsc . "%'";
    $sql .= "     OR CONCAT(IFNULL(u.firstname,''), ' ', u.lastname) LIKE '%" . $qEsc . "%')";
}
$sql .= "   AND u.entity IN (0, " . $entity . ")";
$sql .= " ORDER BY u.firstname, u.lastname LIMIT 8";

$res = $db->query($sql);
if ($res) {
    while ($obj = $db->fetch_object($res)) {
        $fullname = trim($obj->fullname);
        $results[] = [
            'id'        => (int)$obj->id,
            'label'     => $fullname ?: $obj->login,
            'sublabel'  => $obj->login,
            'type'      => 'user',
            'typeLabel' => 'Usuario',
            'login'     => $obj->login,
        ];
    }
}

// For empty query: return only users (default list). Skip contacts & documents.
if ($emptyQ) {
    echo json_encode($results);
    exit;
}

// ── 2. CONTACTS ──────────────────────────────────────────────────────────────
$sql  = "SELECT s.rowid AS id,";
$sql .= " TRIM(CONCAT(IFNULL(s.firstname,''), ' ', s.lastname)) AS label,";
$sql .= " IFNULL(s.email,'') AS sublabel,";
$sql .= " IFNULL(s.fk_soc, 0) AS fk_soc";
$sql .= " FROM " . MAIN_DB_PREFIX . "socpeople s";
$sql .= " WHERE (s.firstname LIKE '%" . $qEsc . "%'";
$sql .= "     OR s.lastname  LIKE '%" . $qEsc . "%'";
$sql .= "     OR CONCAT(IFNULL(s.firstname,''), ' ', s.lastname) LIKE '%" . $qEsc . "%')";
$sql .= "   AND s.entity IN (" . $entity . ")";
if ($socid > 0) $sql .= "   AND s.fk_soc = " . $socid;
$sql .= " ORDER BY s.lastname, s.firstname LIMIT 5";

$res = $db->query($sql);
if ($res) {
    while ($obj = $db->fetch_object($res)) {
        $results[] = [
            'id'        => (int)$obj->id,
            'label'     => trim($obj->label),
            'sublabel'  => $obj->sublabel ?: 'Contacto',
            'fk_soc'    => (int)$obj->fk_soc,
            'type'      => 'contact',
            'typeLabel' => 'Contacto',
        ];
    }
}

// ── 3. PROPOSALS ─────────────────────────────────────────────────────────────
if (isModEnabled('propal')) {
    $sql  = "SELECT rowid AS id, ref AS label FROM " . MAIN_DB_PREFIX . "propal";
    $sql .= " WHERE ref LIKE '%" . $qEsc . "%' AND entity IN (" . $entity . ")";
    if ($socid > 0) $sql .= " AND fk_soc = " . $socid;
    $sql .= " ORDER BY ref LIMIT 5";
    $res = $db->query($sql);
    if ($res) {
        while ($obj = $db->fetch_object($res)) {
            $results[] = ['id' => (int)$obj->id, 'label' => $obj->label,
                'sublabel' => 'Presupuesto', 'type' => 'propal', 'typeLabel' => 'Presupuesto'];
        }
    }
}

// ── 4. ORDERS ────────────────────────────────────────────────────────────────
if (isModEnabled('commande')) {
    $sql  = "SELECT rowid AS id, ref AS label FROM " . MAIN_DB_PREFIX . "commande";
    $sql .= " WHERE ref LIKE '%" . $qEsc . "%' AND entity IN (" . $entity . ")";
    if ($socid > 0) $sql .= " AND fk_soc = " . $socid;
    $sql .= " ORDER BY ref LIMIT 5";
    $res = $db->query($sql);
    if ($res) {
        while ($obj = $db->fetch_object($res)) {
            $results[] = ['id' => (int)$obj->id, 'label' => $obj->label,
                'sublabel' => 'Pedido', 'type' => 'commande', 'typeLabel' => 'Pedido'];
        }
    }
}

// ── 5. INVOICES ───────────────────────────────────────────────────────────────
if (isModEnabled('facture')) {
    $sql  = "SELECT rowid AS id, ref AS label FROM " . MAIN_DB_PREFIX . "facture";
    $sql .= " WHERE ref LIKE '%" . $qEsc . "%' AND entity IN (" . $entity . ")";
    if ($socid > 0) $sql .= " AND fk_soc = " . $socid;
    $sql .= " ORDER BY ref LIMIT 5";
    $res = $db->query($sql);
    if ($res) {
        while ($obj = $db->fetch_object($res)) {
            $results[] = ['id' => (int)$obj->id, 'label' => $obj->label,
                'sublabel' => 'Factura', 'type' => 'facture', 'typeLabel' => 'Factura'];
        }
    }
}

// ── 6. PROJECTS ───────────────────────────────────────────────────────────────
if (isModEnabled('projet')) {
    $sql  = "SELECT rowid AS id, ref AS label, title AS sublabel FROM " . MAIN_DB_PREFIX . "projet";
    $sql .= " WHERE (ref LIKE '%" . $qEsc . "%' OR title LIKE '%" . $qEsc . "%')";
    $sql .= "   AND entity IN (" . $entity . ")";
    if ($socid > 0) $sql .= "   AND fk_soc = " . $socid;
    $sql .= " ORDER BY ref LIMIT 5";
    $res = $db->query($sql);
    if ($res) {
        while ($obj = $db->fetch_object($res)) {
            $results[] = ['id' => (int)$obj->id, 'label' => $obj->label,
                'sublabel' => $obj->sublabel ?: 'Proyecto', 'type' => 'project', 'typeLabel' => 'Proyecto'];
        }
    }
}

// ── 7. PRODUCTS ───────────────────────────────────────────────────────────────
if (isModEnabled('product') || isModEnabled('service')) {
    $sql  = "SELECT p.rowid AS id, p.ref, p.label FROM " . MAIN_DB_PREFIX . "product p";
    $sql .= " WHERE (p.ref LIKE '%" . $qEsc . "%' OR p.label LIKE '%" . $qEsc . "%')";
    $sql .= "   AND p.entity IN (" . $entity . ") AND p.fk_product_type = 0 AND p.tosell = 1";
    $sql .= " ORDER BY p.ref ASC LIMIT 5";
    $res = $db->query($sql);
    if ($res) {
        while ($obj = $db->fetch_object($res)) {
            $results[] = [
                'id'        => (int)$obj->id,
                'label'     => $obj->ref . ($obj->label ? ' - ' . $obj->label : ''),
                'sublabel'  => 'Producto',
                'type'      => 'product',
                'typeLabel' => 'Producto',
                'ref'       => $obj->ref,
            ];
        }
    }
}

// ── 8. SERVICES ───────────────────────────────────────────────────────────────
if (isModEnabled('product') || isModEnabled('service')) {
    $sql  = "SELECT p.rowid AS id, p.ref, p.label FROM " . MAIN_DB_PREFIX . "product p";
    $sql .= " WHERE (p.ref LIKE '%" . $qEsc . "%' OR p.label LIKE '%" . $qEsc . "%')";
    $sql .= "   AND p.entity IN (" . $entity . ") AND p.fk_product_type = 1 AND p.tosell = 1";
    $sql .= " ORDER BY p.ref ASC LIMIT 5";
    $res = $db->query($sql);
    if ($res) {
        while ($obj = $db->fetch_object($res)) {
            $results[] = [
                'id'        => (int)$obj->id,
                'label'     => $obj->ref . ($obj->label ? ' - ' . $obj->label : ''),
                'sublabel'  => 'Servicio',
                'type'      => 'service',
                'typeLabel' => 'Servicio',
                'ref'       => $obj->ref,
            ];
        }
    }
}

// ── 9. BRANCHES (sucursales del tercero actual) ────────────────────────────────
// Solo se muestran cuando hay contexto de tercero (socid > 0)
if ($socid > 0) {
    $sql  = "SELECT s.rowid AS id, s.nom AS name, sp.nom AS parent_name";
    $sql .= " FROM " . MAIN_DB_PREFIX . "societe s";
    $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "societe sp ON s.parent = sp.rowid";
    $sql .= " WHERE s.nom LIKE '%" . $qEsc . "%'";
    $sql .= "   AND s.parent = " . $socid;
    $sql .= "   AND s.entity IN (" . $entity . ") AND s.status = 1";
    $sql .= " ORDER BY s.nom ASC LIMIT 20";
    $res = $db->query($sql);
    if ($res) {
        while ($obj = $db->fetch_object($res)) {
            $results[] = [
                'id'          => (int)$obj->id,
                'label'       => $obj->name,
                'sublabel'    => $obj->parent_name,
                'type'        => 'branch',
                'typeLabel'   => 'Sucursal',
                'parent_name' => $obj->parent_name,
            ];
        }
    }
}

echo json_encode($results);
