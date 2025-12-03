<div class="sidebar">
    <div class="sidebar-header">
        <h2><?php echo APP_NAME; ?></h2>
        <p>Pannello Admin</p>
    </div>
    <ul class="sidebar-menu">
        <li><a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">📊 Dashboard</a></li>
        <li><a href="griglie.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'griglie.php' ? 'active' : ''; ?>">📋 Griglie di Valutazione</a></li>
        <li><a href="utenti.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'utenti.php' ? 'active' : ''; ?>">👥 Gestione Utenti</a></li>
        <li><a href="prove.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'prove.php' ? 'active' : ''; ?>">📝 Prove/UDA</a></li>
        <li><a href="report.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'report.php' ? 'active' : ''; ?>">📈 Report</a></li>
    </ul>
</div>
