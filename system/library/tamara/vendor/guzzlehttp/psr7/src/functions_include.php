<?php

namespace TMS;

// Don't redefine the functions if included multiple times.
if (!\function_exists('TMS\\GuzzleHttp\\Psr7\\str')) {
    require __DIR__ . '/functions.php';
}
