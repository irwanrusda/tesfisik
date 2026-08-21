<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
Auth::requireRole('superadmin');

$q = trim((string) ($_GET['q'] ?? ''));
$module = in_array($_GET['module'] ?? '', ['tes_fisik', 'bleep_test'], true) ? $_GET['module'] : '';
$action = in_array($_GET['action'] ?? '', ['create', 'update', 'delete'], true) ? $_GET['action'] : '';
$from = (string) ($_GET['from'] ?? '');
$to = (string) ($_GET['to'] ?? '');
$where = [];
$params = [];
if ($q !== '') { $where[] = '(athlete_name LIKE ? OR user_name LIKE ? OR username LIKE ? OR record_number LIKE ?)'; array_push($params, "%{$q}%", "%{$q}%", "%{$q}%", "%{$q}%"); }
if ($module !== '') { $where[] = 'module = ?'; $params[] = $module; }
if ($action !== '') { $where[] = 'action = ?'; $params[] = $action; }
if ($from !== '') { $where[] = 'created_at >= ?'; $params[] = $from . ' 00:00:00'; }
if ($to !== '') { $where[] = 'created_at <= ?'; $params[] = $to . ' 23:59:59'; }

$pdo = Database::connection();
$sql = 'SELECT * FROM audit_logs' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY created_at DESC, id DESC LIMIT 500';
$statement = $pdo->prepare($sql);
$statement->execute($params);
$logs = $statement->fetchAll();
$summary = $pdo->query("SELECT COUNT(*) AS total, SUM(action = 'create') AS created, SUM(action = 'update') AS updated, SUM(action = 'delete') AS deleted, COUNT(DISTINCT user_id) AS users FROM audit_logs")->fetch();

view('audit-logs', compact('logs', 'summary', 'q', 'module', 'action', 'from', 'to') + ['pageTitle' => 'Audit Log']);
