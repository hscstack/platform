<?php

// dev-logs.php
// Used by `composer run dev` as the "logs" process in concurrently.
// On Windows, keeps the slot alive; on Unix, runs pail.
if (PHP_OS_FAMILY === 'Windows') {
    while (true) {
        usleep(100000);
    }
} else {
    passthru('php artisan pail --timeout=0');
}
