<?php 
declare(strict_types=1);
if (!defined('ABSPATH')) exit; 
$oracle = new VIS_Oracle();
$res = $oracle->run_prophecy();
?>

<div class="vis-card">
    <h3><span class="dashicons dashicons-visibility"></span> SYSTEM AUDIT REPORT</h3>
    <table class="vis-table">
        <thead>
            <tr>
                <th width="30%">SECURITY CHECK</th>
                <th width="15%">STATUS</th>
                <th>ANALYSIS RESULT</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($res as $r): 
            $badge = $r['status'] == 'PASS' ? 'bg-green' : 'bg-red';
            ?>
            <tr>
                <td style="font-weight:600; color:#fff;"><?php echo $r['check']; ?></td>
                <td><span class="vis-badge <?php echo $badge; ?>"><?php echo $r['status']; ?></span></td>
                <td style="color:var(--vis-text-secondary);"><?php echo $r['msg']; ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>