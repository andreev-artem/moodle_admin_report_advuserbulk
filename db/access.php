<?php

$report_advuserbulk_capabilities = array(

    'report/advuserbulk:view' => array(
        'riskbitmask' => RISK_DATALOSS | RISK_PERSONAL | RISK_SPAM,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array(
            'admin' => CAP_ALLOW,
        ),
    )
);
