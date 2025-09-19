<?php
require_once "../funciones.php";
$pdo = conectarBD();

$action = $_REQUEST['action'] ?? '';

if ($action === 'listar') {
    $stmt = $pdo->query("SELECT * FROM k_notas ORDER BY anclada DESC, fecha_modificacion DESC");
    $notas = $stmt->fetchAll();

    foreach ($notas as &$nota) {
        // Traer checklist
        $c = $pdo->prepare("SELECT * FROM k_checklist_items WHERE id_nota = ?");
        $c->execute([$nota['id']]);
        $items = $c->fetchAll();

        $nota['checklist'] = $items;
        $nota['completada'] = (count($items) > 0 && count(array_filter($items, fn($i) => $i['completado'] == 1)) === count($items));
    }

    echo json_encode($notas);
    exit;
}

if ($action === 'crear') {
    $stmt = $pdo->prepare("INSERT INTO k_notas (titulo, contenido, color) VALUES (?, ?, ?)");
    $ok = $stmt->execute([$_POST['titulo'], $_POST['contenido'], $_POST['color']]);
    echo json_encode(['ok' => $ok]);
    exit;
}

if ($action === 'editar') {
    $stmt = $pdo->prepare("UPDATE k_notas SET titulo=?, contenido=?, color=? WHERE id=?");
    $ok = $stmt->execute([$_POST['titulo'], $_POST['contenido'], $_POST['color'], $_POST['id']]);
    echo json_encode(['ok' => $ok]);
    exit;
}

if ($action === 'eliminar') {
    $stmt = $pdo->prepare("DELETE FROM k_notas WHERE id = ?");
    $ok = $stmt->execute([$_POST['id']]);
    echo json_encode(['ok' => $ok]);
    exit;
}

if ($action === 'agregar_item') {
    $stmt = $pdo->prepare("INSERT INTO k_checklist_items (id_nota, texto) VALUES (?, ?)");
    $ok = $stmt->execute([$_POST['id_nota'], $_POST['texto']]);
    echo json_encode(['ok' => $ok]);
    exit;
}

if ($action === 'toggle_item') {
    $stmt = $pdo->prepare("UPDATE k_checklist_items SET completado=? WHERE id=?");
    $ok = $stmt->execute([$_POST['completado'], $_POST['id']]);
    echo json_encode(['ok' => $ok]);
    exit;
}

echo json_encode(['error' => 'Acción no válida']);
