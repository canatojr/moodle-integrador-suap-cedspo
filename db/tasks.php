<?php
defined('MOODLE_INTERNAL') || die();
$tasks =
    array(
        array(
            'classname' => 'block_suap\task\cron_task',
            'blocking' => 0,
            'minute' => '0',       //run after every 15 mins
            'hour' => '4',
            'day' => '*',
            'dayofweek' => '*',
            'month' => '*',
        )
    );
