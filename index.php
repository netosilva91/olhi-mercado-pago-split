<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ERROR);

header("HTTP/1.1 403 Forbidden", true, 403);
exit;
