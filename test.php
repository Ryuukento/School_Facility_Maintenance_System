<?php
echo "✅ PHP is working! Version: " . PHP_VERSION;
echo "\nServer: " . $_SERVER['SERVER_SOFTWARE'];
echo "\nPath: " . __DIR__;
echo "\nSession Status: " . session_status();
?>
