<div class="sidebar">
    <div class="sidebar-header">
        <h2><?php echo APP_NAME; ?></h2>
        <p>Pannello Docente</p>
    </div>
    <ul class="sidebar-menu">
        <li><a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">📊 Dashboard</a></li>
        <li><a href="uda.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'uda.php' ? 'active' : ''; ?>">📚 Le Mie UDA</a></li>
        <li><a href="prove.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'prove.php' ? 'active' : ''; ?>">📝 Le Mie Prove</a></li>
        <li><a href="griglie.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'griglie.php' ? 'active' : ''; ?>">📋 Griglie Disponibili</a></li>
        <li><a href="studenti.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'studenti.php' ? 'active' : ''; ?>">👨‍🎓 Studenti</a></li>
    </ul>
</div>
