<?php

defined('MOODLE_INTERNAL') || die();

$plugin->version = 2017072600;        // The current plugin version (Date: YYYYMMDDXX)
$plugin->requires = 2016111801;
$plugin->release = '1.0';
$plugin->component = 'block_suap'; // Full name of the plugin (used for diagnostics)
$plugin->maturity = MATURITY_ALPHA;

$plugin->dependencies = array(
    'auth_oauth2' => ANY_VERSION
);
