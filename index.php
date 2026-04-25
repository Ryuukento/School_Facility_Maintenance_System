<?php
// Proxy all root requests through Laravel's real public front controller
// so the project folder itself remains the main URL.
require __DIR__ . '/public/index.php';
