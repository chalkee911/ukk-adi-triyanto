<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require_auth();

$type = 'OUT';
require __DIR__ . '/includes/transaction_page.php';
