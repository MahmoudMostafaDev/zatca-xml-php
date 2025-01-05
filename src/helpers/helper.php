<?php

function dd(...$arg)
{
    echo '<pre>';
    print_r($arg);
    exit;
}

function load_helpers(...$args): void
{
    foreach ($args as $arg)
        require_once $arg . '.php';
}