<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../funciones.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Consultar Compatibilidades</title>
    <link rel="stylesheet" href="estilos.css">

</head>
<body>
<h1 style="display: flex; align-items: center; gap: 10px;">
<span style="
    display: inline-flex;
    width: 40px; 
    height: 40px; 
    background: white; 
    border-radius: 50%; 
    justify-content: center; 
    align-items: center; 
    overflow: hidden;
     border: 0.2px solid black;
  ">
    <img src="../Central-Cell-Logo-JUSTCELL.png" alt="Logo Central Cell" 
         style="width: 30px; height: 30px; object-fit: contain;" />
  </span>  
Consultar Compatibilidades
  
</h1>


<label for="modelo_buscar">Escribe el modelo:</label>
<input type="text" id="modelo_buscar" placeholder="Ej: IPHONE 13">
<input type="hidden" id="modelo_buscar_id">
<ul id="lista_buscar" class="autocomplete-list"></ul>
<br><br>

<label for="tipo_filtro">Tipo (opcional):</label>
<select id="tipo_filtro">
    <option value="">Todos</option>
    <option value="glass">Glass</option>
    <option value="funda">Funda</option>
</select>
<br><br>

<button id="btn_consultar">Consultar</button>

<div id="resultados"></div>

<script>
function setupAutocomplete(inputId, hiddenId, listaId) {
    const input = document.getElementById(inputId);
    const hidden = document.getElementById(hiddenId);
    const lista = document.getElementById(listaId);
    let activeIndex = -1;

    input.addEventListener('input', function() {
        const q = this.value;
        hidden.value = '';
        activeIndex = -1;
        if (!q) {
            lista.innerHTML = '';
            return;
        }
        fetch(`buscar_modelos.php?q=${encodeURIComponent(q)}`)
            .then(res => res.json())
            .then(data => {
                lista.innerHTML = '';
                data.forEach((m,index) => {
                    const li = document.createElement('li');
                    li.textContent = m.marca + ' ' + m.modelo;
                    li.dataset.id = m.id;
                    li.addEventListener('click', () => {
                        input.value = li.textContent;
                        hidden.value = li.dataset.id;
                        lista.innerHTML = '';
                    });
                    lista.appendChild(li);
                });
            });
    });

    input.addEventListener('keydown', function(e) {
        const items = lista.querySelectorAll('li');
        if (!items.length) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeIndex = (activeIndex + 1) % items.length;
            updateActive(items);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeIndex = (activeIndex - 1 + items.length) % items.length;
            updateActive(items);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (activeIndex >= 0 && activeIndex < items.length) {
                items[activeIndex].click();
            } else if (items.length === 1) {
                items[0].click();
            }
        }
    });

    function updateActive(items) {
        items.forEach((item, idx) => {
            item.classList.toggle('active', idx === activeIndex);
            if (idx === activeIndex) item.scrollIntoView({block: "nearest"});
        });
    }
}

// Inicializar autocompletado
setupAutocomplete('modelo_buscar', 'modelo_buscar_id', 'lista_buscar');

// Consultar compatibilidades
document.getElementById('btn_consultar').addEventListener('click', function() {
    const modelo_id = document.getElementById('modelo_buscar_id').value;
    const tipo = document.getElementById('tipo_filtro').value;

    if (!modelo_id) {
        alert("Selecciona un modelo válido");
        return;
    }

    fetch(`buscar_compatibilidades.php?modelo_id=${modelo_id}&tipo=${tipo}`)
        .then(res => res.json())
        .then(data => {
            let html = '';
            if (!data.length) {
                html = '<p>No se encontraron compatibilidades.</p>';
            } else {
                html = '<table><tr><th>Tipo</th><th>Modelo Compatible</th><th>Notas</th></tr>';
                data.forEach(row => {
                    html += `<tr>
                        <td>${row.tipo}</td>
                        <td>${row.modelo}</td>
                        <td>${row.nota}</td>
                    </tr>`;
                });
                html += '</table>';
            }
            document.getElementById('resultados').innerHTML = html;
        });
});
</script>
</body>
</html>
