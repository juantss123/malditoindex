<?php
// logout.php

session_start();
session_unset();
session_destroy();

// Redirigir a index con query string
header("Location: index.php?logout=1");
exit();
