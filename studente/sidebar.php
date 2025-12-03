<div class="sidebar">
    <div class="sidebar-header">
        <h2><?php echo APP_NAME; ?></h2>
        <p>Pannello Studente</p>
    </div>
    <ul class="sidebar-menu">
        <li><a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">📊 Le Mie Valutazioni</a></li>
        <li><a href="storico.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'storico.php' ? 'active' : ''; ?>">📈 Storico Completo</a></li>
    </ul>
</div>
