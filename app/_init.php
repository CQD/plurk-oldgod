<?php
include __DIR__ . '/../vendor/autoload.php';
include __DIR__ . '/config.php';

$qlurk = new \Qlurk\ApiClient(CONSUMER_KEY, CONSUMER_SECRET, ACCESS_TOKEN, ACCESS_TOKEN_SECRET);
