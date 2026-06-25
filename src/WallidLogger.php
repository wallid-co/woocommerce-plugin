<?php

namespace WallidCommerceGateway;

if (!defined('ABSPATH')) {
    exit;
}

function wallid_log($message, $level = 'error')
{
    wc_get_logger()->{$level}($message, ['source' => 'wallid']);
}
