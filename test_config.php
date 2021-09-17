#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config.php';

$qlurk = new \Qlurk\ApiClient(CONSUMER_KEY, CONSUMER_SECRET, ACCESS_TOKEN, ACCESS_TOKEN_SECRET);
$me = $qlurk->call('/APP/Users/me');

$nick = $me['nick_name'] ?? '';
if (!$nick) {
    echo "Failed, nick field not found!\n";
    exit -1;
}
if ('OldGod' !== $nick) {
    echo "Failed, I am not `OldGod`!\n";
    exit -2;
}
echo "Test Config OK\n";
