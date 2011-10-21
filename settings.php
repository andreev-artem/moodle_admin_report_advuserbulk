<?php

defined('MOODLE_INTERNAL') || die;

$ADMIN->add('accounts', new admin_externalpage('reportadvuserbulk', get_string('pluginname', 'report_advuserbulk'), "$CFG->wwwroot/$CFG->admin/report/advuserbulk/user_bulk.php", 'report/advuserbulk:view'));
?>
