<?php

$capabilities = array(

    'block/suap:view' => array(
        'riskbitmask' => RISK_CONFIG | RISK_DATALOSS,
    
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'user' => CAP_PREVENT
        ),

        'clonepermissionsfrom' => 'moodle/my:manageblocks'
    ),
    
    'block/suap:myaddinstance' => array(
        'riskbitmask' => RISK_CONFIG | RISK_DATALOSS,
    
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'user' => CAP_PREVENT
        ),

        'clonepermissionsfrom' => 'moodle/my:manageblocks'
    ),

    'block/suap:addinstance' => array(
        'riskbitmask' => RISK_CONFIG | RISK_DATALOSS,

        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'manager' => CAP_PREVENT,
            'teacher' => CAP_PREVENT
        ),

        'clonepermissionsfrom' => 'moodle/site:manageblocks'
    ),

        
);
