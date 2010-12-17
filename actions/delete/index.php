<?php
/**
* script for bulk user delete operations
*/

require_once('../../../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/'.$CFG->admin.'/report/advuserbulk/lib.php');

$confirm = optional_param('confirm', 0, PARAM_BOOL);

admin_externalpage_setup('reportadvuserbulk');
check_action_capabilities('delete', true);

$return = $CFG->wwwroot.'/'.$CFG->admin.'/report/advuserbulk/user_bulk.php';

if (empty($SESSION->bulk_users)) {
    redirect($return);
}

admin_externalpage_print_header();

//TODO: add support for large number of users

if ($confirm and confirm_sesskey()) {
    $primaryadmin = get_admin();

    $in = implode(',', $SESSION->bulk_users);
    if ($rs = get_recordset_select('user', "id IN ($in)")) {
        while ($user = rs_fetch_next_record($rs)) {
            if ($primaryadmin->id != $user->id and $USER->id != $user->id and delete_user($user)) {
                unset($SESSION->bulk_users[$user->id]);
            } else {
                notify(get_string('deletednot', '', fullname($user, true)));
            }
        }
        rs_close($rs);
    }
    redirect($return, get_string('changessaved'));

} else {
    $in = implode(',', $SESSION->bulk_users);
    $userlist = get_records_select_menu('user', "id IN ($in)", 'fullname', 'id,'.sql_fullname().' AS fullname');
    $usernames = implode(', ', $userlist);
    $optionsyes = array();
    $optionsyes['confirm'] = 1;
    $optionsyes['sesskey'] = sesskey();
    print_heading(get_string('confirmation', 'admin'));
    notice_yesno(get_string('deletecheckfull', '', $usernames), 'index.php', $return, $optionsyes, NULL, 'post', 'get');
}

admin_externalpage_print_footer();
?>
