<?php
// toolbar.php
// Normalize query params
$mode  = $_GET['mode']  ?? 'balanced';
$start = $_GET['start'] ?? date('Y-m-01');
$end   = $_GET['end']   ?? date('Y-m-d');
?>

<div class="container" style="margin-top:5px; margin-bottom:5px;">
    <form method="get" action="<?php echo $mybaseurl; ?>/index.php" class="form-inline" role="form"
          style="display:flex; align-items:center; flex-wrap:nowrap; gap:10px;" id="toolbarForm">

        <!-- Keep user -->
        <input type="hidden" name="uid" value="<?php echo htmlspecialchars($currentUser); ?>">

        <!-- Mode Selector -->
        <label for="mode" class="mb-0">Mode:</label>
        <select name="mode" id="mode" class="form-control input-sm">
            <option value="strict"   <?php echo $currentMode==='strict' ? 'selected' : ''; ?>>Strict</option>
            <option value="balanced" <?php echo $currentMode==='balanced' ? 'selected' : ''; ?>>Balanced</option>
            <option value="generous" <?php echo $currentMode==='generous' ? 'selected' : ''; ?>>Generous</option>
        </select>

        <!-- Quick Range -->
        <label for="quickRange" class="mb-0">Range:</label>
        <select name="quickRange" id="quickRange" class="form-control input-sm">
            <option value="thisWeek"  <?php echo $currentRange==='thisWeek' ? 'selected' : ''; ?>>This Week</option>
            <option value="lastWeek"  <?php echo $currentRange==='lastWeek' ? 'selected' : ''; ?>>Last Week</option>
            <option value="thisMonth"  <?php echo $currentRange==='thisMonth' ? 'selected' : ''; ?>>This Month</option>
            <option value="lastMonth"  <?php echo $currentRange==='lastMonth' ? 'selected' : ''; ?>>Last Month</option>
            <option value="thisQuarter"  <?php echo $currentRange==='thisQuarter' ? 'selected' : ''; ?>>This Quarter</option>
            <option value="lastQuarter"  <?php echo $currentRange==='lastQuarter' ? 'selected' : ''; ?>>Last Quarter</option>
            <option value="thisYear"   <?php echo $currentRange==='thisYear' ? 'selected' : ''; ?>>This Year</option>
            <option value="lastYear"   <?php echo $currentRange==='lastYear' ? 'selected' : ''; ?>>Last Year</option>
            <option value="custom"     <?php echo $currentRange==='custom' ? 'selected' : ''; ?>>Custom</option>
        </select>

        <!-- Start / End -->
        <label for="start" class="mb-0">Start:</label>
        <input type="date" name="start" id="start" class="form-control input-sm"
               value="<?php echo htmlspecialchars($currentStart); ?>">

        <label for="end" class="mb-0">End:</label>
        <input type="date" name="end" id="end" class="form-control input-sm"
               value="<?php echo htmlspecialchars($currentEnd); ?>">

        <!-- Apply Button -->
        <button type="submit" id="applyBtn" class="btn btn-primary btn-sm my-wider-button">Apply</button>

        <!-- Excel button -->
        <a href="javascript:void(0);"
           onclick="return saveToExcel();"
           class="btn btn-success btn-sm hidden-print">
            <i class="glyphicon glyphicon-download-alt"></i> Excel
        </a>
    </form>
</div>

<!-- Hidden Excel Export Form -->
<form action="SaveToExcel.php" name="savetoexcelform" id="savetoexcelform" method="post" target="_blank" onsubmit="return saveToExcel();">
    <input type="hidden" id="dataToDisplay" name="dataToDisplay">
    <input type="hidden" id="filename" name="filename" value="MyTimecard_<?php echo $currentUser.'_'.$currentRange.'_'.date('Ymd'); ?>.xls">
</form>