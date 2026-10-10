<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/config.php';

try {
    crearTablas();
} catch (Throwable $e) {
    http_response_code(500);
    exit('No se pudo conectar a MySQL o crear la tabla. Verifica las credenciales y que la base de datos exista.');
}

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function redirigir(): never { header('Location: index.php'); exit; }

$errores = [];
$editar = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
    $direccion = trim((string)($_POST['direccion'] ?? ''));
    $estado = (string)($_POST['estado'] ?? 'preparando');
    $sencilla = filter_input(INPUT_POST, 'cantidad_sencilla', FILTER_VALIDATE_INT);
    $doble = filter_input(INPUT_POST, 'cantidad_doble', FILTER_VALIDATE_INT);
    $mixta = filter_input(INPUT_POST, 'cantidad_mixta', FILTER_VALIDATE_INT);

    if ($accion === 'eliminar') {
        if ($id > 0) {
            $st = db()->prepare('DELETE FROM pedidos WHERE id = ?');
            $st->execute([$id]);
        }
        redirigir();
    }

    if ($direccion === '' || mb_strlen($direccion) > 255) $errores[] = 'La dirección es obligatoria y debe tener máximo 255 caracteres.';
    if (!in_array($estado, ['preparando', 'despachado'], true)) $errores[] = 'Estado no válido.';
    foreach (['Sencilla' => $sencilla, 'Doble' => $doble, 'Mixta' => $mixta] as $nombre => $cantidad) {
        if ($cantidad === false || $cantidad === null || $cantidad < 0 || $cantidad > 10000) {
            $errores[] = "La cantidad de hamburguesas {$nombre} debe ser un entero entre 0 y 10000.";
        }
    }
    if (!$errores && !in_array($sencilla + $doble + $mixta, [0], true) && ($sencilla + $doble + $mixta) > 0) {
        if ($accion === 'crear') {
            $st = db()->prepare('INSERT INTO pedidos (direccion, estado, cantidad_sencilla, cantidad_doble, cantidad_mixta) VALUES (?, ?, ?, ?, ?)');
            $st->execute([$direccion, $estado, $sencilla, $doble, $mixta]);
            redirigir();
        } elseif ($accion === 'actualizar' && $id > 0) {
            $st = db()->prepare('UPDATE pedidos SET direccion=?, estado=?, cantidad_sencilla=?, cantidad_doble=?, cantidad_mixta=? WHERE id=?');
            $st->execute([$direccion, $estado, $sencilla, $doble, $mixta, $id]);
            redirigir();
        } else {
            $errores[] = 'Acción no válida.';
        }
    } elseif (!$errores) {
        $errores[] = 'El pedido debe contener al menos una hamburguesa.';
    }

    if ($accion === 'actualizar' && $id > 0) {
        $editar = ['id'=>$id, 'direccion'=>$direccion, 'estado'=>$estado, 'cantidad_sencilla'=>$sencilla, 'cantidad_doble'=>$doble, 'cantidad_mixta'=>$mixta];
    }
}

if (isset($_GET['editar'])) {
    $id = filter_input(INPUT_GET, 'editar', FILTER_VALIDATE_INT);
    if ($id) {
        $st = db()->prepare('SELECT * FROM pedidos WHERE id = ?');
        $st->execute([$id]);
        $editar = $st->fetch() ?: null;
    }
}

$filtro = (string)($_GET['estado'] ?? '');
if (in_array($filtro, ['preparando','despachado'], true)) {
    $st = db()->prepare('SELECT * FROM pedidos WHERE estado=? ORDER BY creado_en DESC, id DESC');
    $st->execute([$filtro]);
    $pedidos = $st->fetchAll();
} else {
    $pedidos = db()->query('SELECT * FROM pedidos ORDER BY creado_en DESC, id DESC')->fetchAll();
}
$form = $editar ?: ['id'=>'', 'direccion'=>'', 'estado'=>'preparando', 'cantidad_sencilla'=>1, 'cantidad_doble'=>0, 'cantidad_mixta'=>0];
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Pedidos | Hamburguesería</title>
<style>
:root{--red:#b91c1c;--dark:#1f2937;--bg:#f3f4f6;--muted:#6b7280}
*{box-sizing:border-box}body{margin:0;background:var(--bg);font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;color:#111827}
header{background:var(--dark);color:#fff;padding:24px 16px}header .wrap,main{max-width:1100px;margin:auto}h1{margin:0;font-size:1.7rem}header p{margin:6px 0 0;color:#d1d5db}
main{padding:24px 16px}.layout{display:grid;grid-template-columns: minmax(280px,350px) 1fr;gap:20px;align-items:start}
.card{background:white;border-radius:14px;padding:20px;box-shadow:0 3px 14px #1118270c}h2{font-size:1.15rem;margin:0 0 16px}
label{display:block;font-weight:650;font-size:.92rem;margin:13px 0 6px}input,select{width:100%;padding:10px 11px;border:1px solid #d1d5db;border-radius:8px;font:inherit;background:#fff}
.grid-qty{display:grid;grid-template-columns:repeat(3,1fr);gap:8px}.grid-qty label{font-size:.82rem}.btn{display:inline-block;border:0;border-radius:8px;padding:10px 13px;font-weight:700;text-decoration:none;cursor:pointer;font-size:.9rem}
.primary{background:var(--red);color:white;width:100%;margin-top:16px}.secondary{background:#e5e7eb;color:#111827}.danger{background:#fee2e2;color:#991b1b}.small{padding:7px 9px;font-size:.82rem}
.alert{padding:12px;border-radius:8px;background:#fef2f2;color:#991b1b;margin-bottom:15px}.filters{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px}.table-wrap{overflow-x:auto}table{width:100%;border-collapse:collapse;font-size:.9rem}th,td{text-align:left;padding:12px 10px;border-bottom:1px solid #e5e7eb;vertical-align:top}th{color:#6b7280;font-size:.78rem;text-transform:uppercase;letter-spacing:.03em}.status{display:inline-block;padding:4px 8px;border-radius:999px;font-size:.78rem;font-weight:700}.preparando{background:#fef3c7;color:#92400e}.despachado{background:#dcfce7;color:#166534}.actions{display:flex;gap:6px;flex-wrap:wrap}.inline{display:inline}.muted{color:var(--muted);font-size:.88rem}.qty{white-space:nowrap}.empty{text-align:center;padding:30px;color:var(--muted)}
@media(max-width:800px){.layout{grid-template-columns:1fr}header{padding:20px 16px}th,td{padding:10px 8px}}
</style>
</head>
<body>
<header><div class="wrap"><h1>🍔 Gestión de pedidos</h1><p>Administración de pedidos de la hamburguesería</p></div></header>
<main>
<div class="layout">
<section class="card">
<h2><?= $editar ? 'Editar pedido #'.h($form['id']) : 'Registrar pedido' ?></h2>
<?php if ($errores): ?><div class="alert"><?php foreach ($errores as $error): ?><div><?= h($error) ?></div><?php endforeach; ?></div><?php endif; ?>
<form method="post" action="index.php">
<input type="hidden" name="accion" value="<?= $editar ? 'actualizar' : 'crear' ?>">
<?php if ($editar): ?><input type="hidden" name="id" value="<?= h($form['id']) ?>"><?php endif; ?>
<label for="direccion">Dirección de entrega</label>
<input id="direccion" name="direccion" maxlength="255" required value="<?= h($form['direccion']) ?>" placeholder="Ej. Calle 10 # 20-30">
<label for="estado">Estado del pedido</label>
<select id="estado" name="estado" required>
<option value="preparando" <?= $form['estado']==='preparando'?'selected':'' ?>>Preparando</option>
<option value="despachado" <?= $form['estado']==='despachado'?'selected':'' ?>>Despachado</option>
</select>
<div class="grid-qty">
<div><label for="sencilla">Sencilla</label><input id="sencilla" type="number" name="cantidad_sencilla" min="0" max="10000" required value="<?= h($form['cantidad_sencilla']) ?>"></div>
<div><label for="doble">Doble</label><input id="doble" type="number" name="cantidad_doble" min="0" max="10000" required value="<?= h($form['cantidad_doble']) ?>"></div>
<div><label for="mixta">Mixta</label><input id="mixta" type="number" name="cantidad_mixta" min="0" max="10000" required value="<?= h($form['cantidad_mixta']) ?>"></div>
</div>
<button class="btn primary" type="submit"><?= $editar ? 'Guardar cambios' : 'Crear pedido' ?></button>
<?php if ($editar): ?><a class="btn secondary" style="display:block;text-align:center;margin-top:8px" href="index.php">Cancelar edición</a><?php endif; ?>
</form>
<p class="muted">La tabla de pedidos se crea automáticamente si todavía no existe.</p>
</section>
<section class="card">
<h2>Pedidos registrados (<?= count($pedidos) ?>)</h2>
<div class="filters">
<a class="btn secondary small" href="index.php">Todos</a>
<a class="btn secondary small" href="?estado=preparando">Preparando</a>
<a class="btn secondary small" href="?estado=despachado">Despachados</a>
</div>
<?php if (!$pedidos): ?><div class="empty">No hay pedidos para mostrar.</div><?php else: ?>
<div class="table-wrap"><table>
<thead><tr><th>Pedido</th><th>Dirección / Estado</th><th>Hamburguesas</th><th>Acciones</th></tr></thead>
<tbody>
<?php foreach ($pedidos as $p): ?>
<tr>
<td><strong>#<?= h($p['id']) ?></strong><br><span class="muted"><?= h(date('d/m/Y H:i', strtotime($p['creado_en']))) ?></span></td>
<td><?= h($p['direccion']) ?><br><span class="status <?= h($p['estado']) ?>"><?= $p['estado']==='preparando'?'Preparando':'Despachado' ?></span></td>
<td class="qty">Sencilla: <?= h($p['cantidad_sencilla']) ?><br>Doble: <?= h($p['cantidad_doble']) ?><br>Mixta: <?= h($p['cantidad_mixta']) ?><br><strong>Total: <?= (int)$p['cantidad_sencilla']+(int)$p['cantidad_doble']+(int)$p['cantidad_mixta'] ?></strong></td>
<td><div class="actions"><a class="btn secondary small" href="?editar=<?= h($p['id']) ?>">Editar</a>
<form class="inline" method="post" action="index.php" onsubmit="return confirm('¿Eliminar el pedido #<?= h($p['id']) ?>? Esta acción no se puede deshacer.');">
<input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?= h($p['id']) ?>">
<button class="btn danger small" type="submit">Eliminar</button></form></div></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
</section>
</div>
</main>
</body>
</html>
