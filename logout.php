<?php
session_start();
session_unset();
session_destroy();

// Pass a query parameter to index.php
header("Location: index.php?logout=success");
exit();
?>
