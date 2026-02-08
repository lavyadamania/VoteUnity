<?php
session_start();
session_destroy();
header('Location: /voting/index.php');
exit;
?>