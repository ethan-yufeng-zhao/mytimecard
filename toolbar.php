<?php
// toolbar.php
// Normalize query params
$currentUser  = $requested_user_id;
$currentMode  = $_GET['mode'] ?? 'balanced';
$currentRange = $_GET['quickRange'] ?? 'thisMonth';   // NEW
$currentStart = $_GET['start'] ?? date('Y-m-01');
$currentEnd   = $_GET['end'] ?? date('Y-m-d');
$currentTeam = $_GET['team'] ?? '';

// Helper to build query URL
switch ($currentRange) {
    case 'thisWeek':
        $currentStart = date('Y-m-d', strtotime('monday this week'));
        $currentEnd   = date('Y-m-d');
        break;
    case 'lastWeek':
        $currentStart = date('Y-m-d', strtotime('monday last week'));
        $currentEnd   = date('Y-m-d', strtotime('sunday last week'));
        break;
    case 'thisMonth':
        $currentStart = date('Y-m-01');
        $currentEnd   = date('Y-m-d');
        break;
    case 'lastMonth':
        $currentStart = date('Y-m-01', strtotime('first day of last month'));
        $currentEnd   = date('Y-m-t', strtotime('last day of last month'));
        break;
    case 'thisQuarter':
        $quarter = ceil(date('n') / 3);
        $currentStart = date('Y-m-d', strtotime(date('Y').'-'.(($quarter-1)*3+1).'-01'));
        $currentEnd   = date('Y-m-d'); //date('Y-m-t', strtotime($thisQuarterStart));
        break;
    case 'lastQuarter':
        $quarter = ceil(date('n') / 3);
        $lastQuarter = $quarter - 1;
        if ($lastQuarter < 1) {
            $lastQuarter = 4;
            $lastQuarterYear = date('Y') - 1;
        } else {
            $lastQuarterYear = date('Y');
        }

        // Start of last quarter
        $currentStart = date('Y-m-d', strtotime($lastQuarterYear.'-'.(($lastQuarter-1)*3+1).'-01'));

        // End of last quarter: last day of the last month in that quarter
        $lastQuarterEndMonth = $lastQuarter * 3; // March, June, Sep, Dec
        $currentEnd = date('Y-m-t', strtotime($lastQuarterYear.'-'.$lastQuarterEndMonth.'-01'));
        break;
    case 'thisYear':
        $currentStart = date('Y-01-01');
        $currentEnd   = date('Y-m-d');
        break;
    case 'lastYear':
        $currentStart = date('Y-01-01', strtotime('last year'));
        $currentEnd   = date('Y-12-31', strtotime('last year'));
        break;
    case 'custom':
    default:
        // Respect user input if custom
        $currentStart = $_GET['start'] ?? date('Y-m-01');
        $currentEnd   = $_GET['end'] ?? date('Y-m-d');
        break;
}

$currentQueryUrl = buildQueryUrl($mybaseurl.'/index.php?', $currentUser, $currentMode, $currentStart, $currentEnd, $currentRange, $currentTeam); // default/current

// Debug print
if (DEBUG) {
    echo "<div style='padding:5px; background:#f0f0f0; border:1px solid #ccc;'>";
    echo "DEBUG URL: <a href='$currentQueryUrl'>$currentQueryUrl</a><br>";
    echo "GET Parameters: <pre>".htmlspecialchars(print_r($_GET,true))."</pre>";
    echo "</div>";
}
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

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const quickRange = document.getElementById("quickRange");
        const startInput = document.getElementById("start");
        const endInput   = document.getElementById("end");

        function switchToCustom() {
            if (quickRange.value !== "custom") {
                quickRange.value = "custom";
            }
        }

        startInput.addEventListener("change", switchToCustom);
        endInput.addEventListener("change", switchToCustom);

        // auto-submit when selecting a quick range (except custom)
        quickRange.addEventListener("change", function() {
            if (this.value !== "custom") {
                this.form.submit();
            }
        });
    });
</script>

<script>
    const toolbarForm = document.getElementById('toolbarForm');

    const today = new Date();
    function formatDate(d) { return d.toISOString().slice(0,10); }
    function getWeekStart(d) { const day=d.getDay(); const diff=d.getDate()-day+(day===0?-6:1); return new Date(d.setDate(diff)); }
    function getQuarterStart(d) { const q=Math.floor(d.getMonth()/3); return new Date(d.getFullYear(), q*3, 1); }
    function getQuarterEnd(d) { const q=Math.floor(d.getMonth()/3); return new Date(d.getFullYear(), q*3+3, 0); }

    function setQuickRange(range) {
        let start, end;
        const now = new Date();
        switch(range) {
            case 'thisWeek': start=getWeekStart(new Date()); end=new Date(); break;
            case 'lastWeek': const lw=new Date(); lw.setDate(lw.getDate()-7); start=getWeekStart(lw); end=new Date(start); end.setDate(end.getDate()+6); break;
            case 'thisMonth': start=new Date(now.getFullYear(), now.getMonth(), 1); end=new Date(); break;
            case 'lastMonth': start=new Date(now.getFullYear(), now.getMonth()-1, 1); end=new Date(now.getFullYear(), now.getMonth(), 0); break;
            case 'thisQuarter': start=getQuarterStart(now); end=new Date(); break;
            case 'lastQuarter': const lq=getQuarterStart(now); lq.setMonth(lq.getMonth()-3); start=lq; end=getQuarterEnd(lq); break;
            case 'thisYear': start=new Date(now.getFullYear(),0,1); end=new Date(); break;
            case 'lastYear': start=new Date(now.getFullYear()-1,0,1); end=new Date(now.getFullYear()-1,11,31); break;
        }
        document.getElementById('start').value=formatDate(start);
        document.getElementById('end').value=formatDate(end);
        toolbarForm.submit(); // automatically submit the form
    }

    document.getElementById('quickRange').addEventListener('change', function(){
        setQuickRange(this.value);
    });
</script>