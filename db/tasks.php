<?php
defined('MOODLE_INTERNAL') || die();
$horas=array(23,0,1,2,3,4,5,6);
$minutos=array(0,15,30,45);

$tasks =
    array(
        array(
            'classname' => 'block_suap\task\cron',
            'blocking' => 0,
            'minute' => $minutos[array_rand($minutos)],       //run after every 15 mins
            'hour' => $horas[array_rand($horas)],
            'day' => '*',
            'dayofweek' => '*',
            'month' => '*',
        )
    );
