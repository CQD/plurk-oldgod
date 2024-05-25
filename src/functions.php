<?php

function qlog($level, $msg)
{
    if (defined('NO_QLOG')) {
        return;
    }

    if (defined('STDERR')) {
        $full_msg = sprintf("\033[33m[%s]\033[1;30m %s\033[m\n", date('Y-m-d H:i:s'), $msg);
        fputs(STDERR, $full_msg);
    } else {
        syslog($level, $msg);
    }
}
