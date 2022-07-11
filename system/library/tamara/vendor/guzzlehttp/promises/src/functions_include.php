<?php

namespace TMS;

// Don't redefine the functions if included multiple times.
if (!\function_exists('TMS\\GuzzleHttp\\Promise\\promise_for')) {
    require __DIR__ . '/functions.php';
}
