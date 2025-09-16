<?php
// toolbar.php
// Normalize query params
$mode  = $_GET['mode']  ?? 'balanced';
$start = $_GET['start'] ?? date('Y-m-01');
$end   = $_GET['end']   ?? date('Y-m-d');
?>

<form method="get" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="form-inline toolbar-form mb-3">
    <label class="me-2">Mode:</label>
    <select name="mode" class="form-control me-3">
        <option value="balanced" <?= $mode === 'balanced' ? 'selected' : '' ?>>Balanced</option>
        <option value="detailed" <?= $mode === 'detailed' ? 'selected' : '' ?>>Detailed</option>
    </select>

    <label class="me-2">Start:</label>
    <input type="date" name="start" value="<?= htmlspecialchars($start) ?>" class="form-control me-3">

    <label class="me-2">End:</label>
    <input type="date" name="end" value="<?= htmlspecialchars($end) ?>" class="form-control me-3">

    <button type="submit" class="btn btn-primary me-2">Apply</button>

    <!-- Optional: Excel export -->
    <a href="javascript:void(0);" onclick="document.getElementById('savetoexcelform').submit();"
       class="btn btn-success">
        <i class="glyphicon glyphicon-download-alt"></i> Excel
    </a>
</form>
