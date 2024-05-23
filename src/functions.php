<?php

function qlog($level, $msg)
{
    if (defined('NO_QLOG')) {
        return;
    }

    if (defined('STDERR')) {
        fputs(STDERR, $msg . "\n");
    } else {
        syslog($level, $msg);
    }
}
