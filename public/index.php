<?php

declare(strict_types=1);

use LogsGuard\Config\Database;

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Database.php';

const PAGE_SIZE = 100;

function escape(string|int|null $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$logs = [];
$errorMessage = null;
$riskFilter = $_GET['risk_level'] ?? '';
$eventFilter = trim((string) ($_GET['event_type'] ?? ''));
$allowedRiskLevels = ['low', 'medium', 'high', 'critical'];

if (!in_array($riskFilter, $allowedRiskLevels, true)) {
    $riskFilter = '';
}

try {
    $connection = Database::getConnection();
    $conditions = [];
    $parameters = [];

    if ($riskFilter !== '') {
        $conditions[] = 'risk_level = :risk_level';
        $parameters['risk_level'] = $riskFilter;
    }

    if ($eventFilter !== '') {
        $conditions[] = 'event_type = :event_type';
        $parameters['event_type'] = $eventFilter;
    }

    $query = 'SELECT id, username, action, ip_address, occurred_at, risk_level, event_type, details
        FROM audit_logs';
    if ($conditions !== []) {
        $query .= ' WHERE ' . implode(' AND ', $conditions);
    }
    $query .= ' ORDER BY occurred_at DESC LIMIT ' . PAGE_SIZE;

    $statement = $connection->prepare($query);
    $statement->execute($parameters);
    $logs = $statement->fetchAll();
} catch (RuntimeException $exception) {
    $errorMessage = $exception->getMessage();
} catch (PDOException $exception) {
    error_log('LogsGuard: error consultando auditoría: ' . $exception->getMessage());
    $errorMessage = 'No fue posible cargar los registros de auditoría.';
}

$riskLabels = [
    'low' => 'Bajo',
    'medium' => 'Medio',
    'high' => 'Alto',
    'critical' => 'Crítico',
];

$riskClasses = [
    'low' => 'success',
    'medium' => 'warning',
    'high' => 'danger',
    'critical' => 'dark',
];
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>LogsGuard | Auditoría</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-dark shadow-sm">
        <div class="container">
            <span class="navbar-brand fw-semibold">LogsGuard</span>
            <span class="text-white-50 small">AuditTrail</span>
        </div>
    </nav>

    <main class="container py-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
            <div>
                <h1 class="h3 mb-1">Panel de auditoría</h1>
                <p class="text-secondary mb-0">Actividad reciente de la aplicación</p>
            </div>
            <span class="badge text-bg-primary fs-6"><?= count($logs) ?> registros</span>
        </div>

        <?php if ($errorMessage !== null): ?>
            <div class="alert alert-danger" role="alert">
                <?= escape($errorMessage) ?>
            </div>
        <?php else: ?>
            <form method="get" class="card card-body shadow-sm mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="risk_level" class="form-label">Nivel de riesgo</label>
                        <select id="risk_level" name="risk_level" class="form-select">
                            <option value="">Todos</option>
                            <?php foreach ($riskLabels as $value => $label): ?>
                                <option value="<?= escape($value) ?>" <?= $riskFilter === $value ? 'selected' : '' ?>>
                                    <?= escape($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label for="event_type" class="form-label">Tipo de evento</label>
                        <input id="event_type" name="event_type" class="form-control"
                               value="<?= escape($eventFilter) ?>" maxlength="80"
                               placeholder="Ej. authentication">
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button class="btn btn-primary flex-grow-1" type="submit">Filtrar</button>
                        <a class="btn btn-outline-secondary" href="<?= escape($_SERVER['PHP_SELF'] ?? '/') ?>">Limpiar</a>
                    </div>
                </div>
            </form>

            <div class="card shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th scope="col">Fecha</th>
                                <th scope="col">Usuario</th>
                                <th scope="col">Acción</th>
                                <th scope="col">Evento</th>
                                <th scope="col">IP</th>
                                <th scope="col">Riesgo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($logs === []): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-secondary py-4">
                                        No hay registros para los filtros seleccionados.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <?php $risk = (string) ($log['risk_level'] ?? 'low'); ?>
                                    <tr>
                                        <td class="text-nowrap"><?= escape($log['occurred_at'] ?? null) ?></td>
                                        <td class="fw-semibold"><?= escape($log['username'] ?? null) ?></td>
                                        <td><?= escape($log['action'] ?? null) ?></td>
                                        <td><span class="badge text-bg-light"><?= escape($log['event_type'] ?? null) ?></span></td>
                                        <td><code><?= escape($log['ip_address'] ?? null) ?></code></td>
                                        <td>
                                            <span class="badge text-bg-<?= escape($riskClasses[$risk] ?? 'secondary') ?>">
                                                <?= escape($riskLabels[$risk] ?? $risk) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
