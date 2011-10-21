<?php
/**
* script for bulk user delete operations
*/

require_once('../../../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/'.$CFG->admin.'/report/advuserbulk/lib.php');

$confirm = optional_param('confirm', 0, PARAM_BOOL);

admin_externalpage_setup('reportadvuserbulk');
check_action_capabilities('confirm', true);

$return = $CFG->wwwroot.'/'.$CFG->admin.'/report/advuserbulk/user_bulk.php';

if (empty($SESSION->bulk_users)) {
    redirect($return);
}

//TODO: add support for large number of users

if ($confirm and confirm_sesskey()) {
    list($in, $params) = $DB->get_in_or_equal($SESSION->bulk_users);
    if ($rs = $DB->get_recordset_select('user', "id $in", $params, '', 'id, username, secret, confirmed, auth, firstname, lastname')) {
        foreach ($rs as $user) {
            if ($user->confirmed) {
                continue;
            }
            $auth = get_auth_plugin($user->auth);
            $result = $auth->user_confirm($user->username, $user->secret);
            if ($result != AUTH_CONFIRM_OK && $result != AUTH_CONFIRM_ALREADY) {
                echo $OUTPUT->notification(get_string('usernotconfirmed', '', fullname($user, true)));
            }
        }
        $rs->close();
    }
    redirect($return, get_string('changessaved'));
} else {
    echo $OUTPUT->header();

    list($in, $params) = $DB->get_in_or_equal($SESSION->bulk_users);
    $userlist = $DB->get_records_select_menu('user', "id $in", $params, 'fullname', 'id,'.$DB->sql_fullname().' AS fullname');
    $usernames = implode(', ', $userlist);
    echo $OUTPUT->heading(get_string('confirmation', 'admin'));
    $formcontinue = new single_button(new moodle_url('index.php', array('confirm' => 1)), get_string('yes'));
    $formcancel = new single_button(new moodle_url($return), get_string('no'), 'get');
    echo $OUTPUT->confirm(get_string('confirmcheckfull', '', $usernames), $formcontinue, $formcancel);

    echo $OUTPUT->footer();
}

?>