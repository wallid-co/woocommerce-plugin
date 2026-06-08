<?php

namespace WallidCommerceGateway;

function wallid_log($message, $level = 'error')
{
    wc_get_logger()->{$level}($message, ['source' => 'wallid']);
}
