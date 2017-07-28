<?php

defined('MOODLE_INTERNAL') || die();

$plugin->version = 2017072813.01;       // 20170728      = date YYYYMMDD
                                        //         RR    = release increments - 00.
                                        //           .XX = incremental changes.

$plugin->requires = 2017051501.00;
$plugin->release = '1.00';
$plugin->component = 'block_suap'; // Full name of the plugin (used for diagnostics)
$plugin->maturity = MATURITY_ALPHA;

$plugin->dependencies = array(
    'auth_oauth2' => ANY_VERSION
);
