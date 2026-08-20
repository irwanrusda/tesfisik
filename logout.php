<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

if (request_method('POST')) {
    verify_csrf();
    Auth::logout();
}

redirect('login.php');
