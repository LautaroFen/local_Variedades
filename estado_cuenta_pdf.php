<?php
include("conexion.php");

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$id = intval($_GET['id']);

// Obtener datos del cliente
$stmt = mysqli_prepare($conn, "SELECT * FROM clientes WHERE id=?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);

if (!$resultado || mysqli_num_rows($resultado) != 1) {
    mysqli_stmt_close($stmt);
    die("Cliente no encontrado");
}

$cliente = mysqli_fetch_assoc($resultado);
mysqli_stmt_close($stmt);

// Obtener pagos del cliente
$query_pagos = "SELECT * FROM pagos_clientes WHERE cliente_id = $id ORDER BY numero_cuota ASC";
$resultado_pagos = mysqli_query($conn, $query_pagos);

// Calcular estadó­sticas
$total_cuotas = 0;
$cuotas_pagadas = 0;
$proximo_pago = null;

if ($resultado_pagos && mysqli_num_rows($resultado_pagos) > 0) {
    $total_cuotas = mysqli_num_rows($resultado_pagos);
    mysqli_data_seek($resultado_pagos, 0);
    while ($pago = mysqli_fetch_assoc($resultado_pagos)) {
        if ($pago['estado'] == 'pagado') {
            $cuotas_pagadas++;
        } elseif ($proximo_pago === null && $pago['estado'] == 'pendiente') {
            $proximo_pago = $pago;
        }
    }
}

$progreso = $total_cuotas > 0 ? ($cuotas_pagadas / $total_cuotas) * 100 : 0;
$sena = isset($cliente['sena']) ? $cliente['sena'] : 0;
$saldo_restante = $cliente['valor_total'] - $sena;
$monto_por_cuota = $cliente['cuotas'] > 0 ? $saldo_restante / $cliente['cuotas'] : 0;

// =====================================================
// Descargar PDF real (sin imprimir)
// Requiere: composer require dompdf/dompdf
// URL: estado_cuenta_pdf.php?id=123&download=1
// =====================================================
if (isset($_GET['download']) && (string)$_GET['download'] === '1') {
    require_once __DIR__ . '/includes/pdf_estado_cuenta.php';

    $pagos = [];
    if ($resultado_pagos && mysqli_num_rows($resultado_pagos) > 0) {
        mysqli_data_seek($resultado_pagos, 0);
        while ($pago = mysqli_fetch_assoc($resultado_pagos)) {
            $pagos[] = $pago;
        }
    }

    try {
        $filename = mv_estado_cuenta_filename($cliente);
        $pdfBytes = mv_estado_cuenta_pdf_bytes(
            $cliente,
            $pagos,
            [
                'cuotas_pagadas' => (int)$cuotas_pagadas,
                'total_cuotas' => (int)$total_cuotas,
            ]
        );
    } catch (Throwable $e) {
        header('Content-Type: text/plain; charset=UTF-8');
        echo "ERROR al generar PDF: " . $e->getMessage() . "\n";
        exit;
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo $pdfBytes;
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estado de Cuenta - <?php echo htmlspecialchars($cliente['nombre_completo']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print {
                display: none;
            }

            body {
                margin: 0;
                background: white !important;
            }

            .card {
                box-shadow: none !important;
                border: 1px solid #ddd !important;
            }
        }

        body {
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
    </style>
</head>

<body>
    <main class="container my-4">
        <div class="row justify-content-center">
            <div class="col-12 col-md-10 col-lg-8">
                <div class="card border shadow-sm p-4">

                    <!-- Encabezado -->
                    <div class="text-center mb-4">
                        <h2 class="fw-bold mb-0">Mujeres Virtuosas</h2>
                        <div class="text-muted">Estado de Cuenta</div>
                    </div>

                    <!-- Datos del cliente -->
                    <div class="mb-3">
                        <h6 class="border-bottom pb-1 fw-bold">Datos del cliente</h6>
                        <p class="mb-1"><strong>Nombre:</strong> <?= htmlspecialchars($cliente['nombre_completo']) ?></p>
                        <p class="mb-1"><strong>Teléfono:</strong> <?= htmlspecialchars($cliente['telefono']) ?></p>
                        <p class="mb-1"><strong>Dirección:</strong> <?= htmlspecialchars($cliente['direccion']) ?></p>
                    </div>

                    <!-- Artículos -->
                    <div class="mb-3">
                        <h6 class="border-bottom pb-1 fw-bold">Artículos</h6>
                        <p class="mb-0"><?= nl2br(htmlspecialchars($cliente['articulos'])) ?></p>
                    </div>

                    <!-- Detalle económico (TEXTO PLANO) -->
                    <div class="mb-3">
                        <h6 class="border-bottom pb-1 fw-bold">Detalle económico</h6>
                        <pre class="mb-0">
Valor total     : $<?= number_format($cliente['valor_total'], 2, ',', '.') ?>
Seña / Adelanto : $<?= number_format($sena, 2, ',', '.') ?>
Saldo restante  : $<?= number_format($saldo_restante, 2, ',', '.') ?>

Cuotas          : <?= $cliente['cuotas'] ?>
Monto por cuota : $<?= number_format($monto_por_cuota, 2, ',', '.') ?>
                    </pre>
                    </div>

                    <!-- Calendario de pagos -->
                    <div class="mb-3">
                        <h6 class="border-bottom pb-1 fw-bold">Calendario de pagos</h6>
                        <pre class="mb-0">
Cuota | Fecha Prog |     Monto     | Estado    | Fecha Pago
-----------------------------------------------------------
<?php
mysqli_data_seek($resultado_pagos, 0);
while ($pago = mysqli_fetch_assoc($resultado_pagos)) {
    printf(
        "%5s | %10s | $%11s | %-9s | %10s\n",
        $pago['numero_cuota'],
        date('d/m/Y', strtotime($pago['fecha_programada'])),
        number_format($pago['monto'], 2, ',', '.'),
        strtoupper($pago['estado']),
        $pago['fecha_pago'] ? date('d/m/Y', strtotime($pago['fecha_pago'])) : '-'
    );
}
?>
                    </pre>
                    </div>

                    <!-- Footer -->
                    <div class="text-center pt-3 border-top">
                        <small class="text-muted">
                            Documento generado el <?= date('d/m/Y H:i') ?>
                        </small>
                    </div>

                    <!-- Acciones -->
                    <div class="text-center mt-3 no-print">
                        <button onclick="window.print(); setTimeout(() => window.close(), 500)" class="btn btn-primary">Imprimir</button>
                        <a href="estado_cuenta_pdf.php?id=<?= (int)$id ?>&download=1" class="btn btn-success">Descargar PDF</a>
                        <button onclick="window.close()" class="btn btn-secondary">Volver</button>
                    </div>

                </div>
            </div>
        </div>
    </main>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="javascript/estado_cuenta_pdf.js?v=<?php echo time(); ?>"></script>
</body>

</html>