<?php
require_once "../funciones.php";
$pdo = conectarBD();

// --- API interna ---
if (isset($_GET['api'])) {
    $action = $_GET['api'];

    if ($action === 'listar') {
        $notas = $pdo->query("SELECT * FROM k_notas ORDER BY fecha_modificacion DESC")->fetchAll();
        foreach ($notas as &$n) {
            // Está completada si está archivada
            $n['completada'] = ($n['archivada'] == 1);
        }
        header('Content-Type: application/json');
        echo json_encode($notas);
        exit;
    }

    if ($action === 'crear') {
        $stmt = $pdo->prepare("INSERT INTO k_notas (titulo, contenido, color) VALUES (?, ?, ?)");
        echo json_encode(['ok' => $stmt->execute([$_POST['titulo'], $_POST['contenido'], $_POST['color']])]);
        exit;
    }

    if ($action === 'editar') {
        $stmt = $pdo->prepare("UPDATE k_notas SET titulo=?, contenido=?, color=? WHERE id=?");
        echo json_encode(['ok' => $stmt->execute([$_POST['titulo'], $_POST['contenido'], $_POST['color'], $_POST['id']])]);
        exit;
    }

    if ($action === 'eliminar') {
        $stmt = $pdo->prepare("DELETE FROM k_notas WHERE id=?");
        echo json_encode(['ok' => $stmt->execute([$_POST['id']])]);
        exit;
    }

    if ($action === 'marcar_hecha') {
        // Marcar como completada
        $stmt = $pdo->prepare("UPDATE k_notas SET archivada = 1 WHERE id=?");
        $stmt->execute([$_POST['id']]);
        echo json_encode(['ok' => true]);
        exit;
    }

    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Notas - Simple Keep</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        h1, h2 {
            text-align: center;
            font-weight: 500;
        }
        form {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.1);
            max-width: 500px;
            margin: auto;
        }
        input[type="text"], textarea {
            width: 100%;
            padding: 8px;
            margin-top: 6px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
        }
        button {
            background: #4CAF50;
            color: white;
            padding: 8px 14px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }
        button:hover {
            background: #45a049;
        }
        .contenedor-notas {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .nota {
            background: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
        }
        .nota form {
            background: none;
            box-shadow: none;
            padding: 0;
            margin: 0;
        }
        .nota textarea {
            resize: vertical;
        }
        .nota button {
            margin-top: 5px;
            margin-right: 5px;
        }
        .acciones {
            margin-top: auto;
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
    </style>
</head>
<body>
<h1>Mis Notas</h1>

<h2>Crear Nota</h2>
<form id="formNueva">
    <input type="text" name="titulo" placeholder="Título"><br>
    <textarea name="contenido" placeholder="Contenido"></textarea><br>
    <input type="text" name="color" value="blanco"><br>
    <button>Guardar</button>
</form>
<hr>

<h2>Notas Activas</h2>
<div id="notasActivas"></div>

<h2>Notas Completadas</h2>
<div id="notasCompletadas"></div>

<script>
async function cargarNotas() {
    const res = await fetch('?api=listar');
    const notas = await res.json();
    let htmlActivas = '', htmlComp = '';

    notas.forEach(n => {
        const bloque = `
        <div style="border:1px solid black; margin:5px; padding:5px;">
            <form onsubmit="return editarNota(${n.id}, this)">
                <input type="text" name="titulo" value="${n.titulo}"><br>
                <textarea name="contenido">${n.contenido}</textarea><br>
                <input type="text" name="color" value="${n.color}"><br>
                <button>Actualizar</button>
            </form>
            <button onclick="marcarHecha(${n.id})">✅ Hecho</button>
            <button onclick="eliminarNota(${n.id})">🗑 Eliminar</button>
        </div>`;

        if (n.completada) {
            htmlComp += bloque;
        } else {
            htmlActivas += bloque;
        }
    });

    document.getElementById('notasActivas').innerHTML = htmlActivas;
    document.getElementById('notasCompletadas').innerHTML = htmlComp;
}

document.getElementById('formNueva').onsubmit = async e => {
    e.preventDefault();
    const fd = new FormData(e.target);
    const res = await fetch('?api=crear', {method:'POST', body:fd});
    const r = await res.json();
    if (r.ok) { e.target.reset(); cargarNotas(); }
};

async function editarNota(id, form) {
    const fd = new FormData(form);
    fd.append('id', id);
    const res = await fetch('?api=editar', {method:'POST', body:fd});
    const r = await res.json();
    if (r.ok) cargarNotas();
    return false;
}

async function eliminarNota(id) {
    if (!confirm("¿Eliminar nota?")) return;
    const fd = new FormData();
    fd.append('id', id);
    const res = await fetch('?api=eliminar', {method:'POST', body:fd});
    const r = await res.json();
    if (r.ok) cargarNotas();
}

async function marcarHecha(id) {
    const fd = new FormData();
    fd.append('id', id);
    const res = await fetch('?api=marcar_hecha', {method:'POST', body:fd});
    const r = await res.json();
    if (r.ok) cargarNotas();
}

cargarNotas();
</script>
</body>
</html>
