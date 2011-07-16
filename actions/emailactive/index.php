<?php
/**
* miniscript for bulk user email activation/deactivation
*/

require_once('../../../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/'.$CFG->admin.'/report/advuserbulk/lib.php');

$confirm  = optional_param('confirm', 0, PARAM_BOOL);
$mailstop = optional_param('mailstop', false, PARAM_RAW);

admin_externalpage_setup('reportadvuserbulk');
check_action_capabilities('emailactive', true);

$return = $CFG->wwwroot.'/'.$CFG->admin.'/report/advuserbulk/user_bulk.php';
$langdir = $CFG->dirroot.'/admin/report/advuserbulk/actions/emailactive/lang/';
$pluginname = ACTIONS_LANG_PREFIX.'emailactive';

if (empty($SESSION->bulk_users)) {
    redirect($return);
}

echo $OUTPUT->header();

if ($confirm and confirm_sesskey()) {
    foreach ($SESSION->bulk_users as $user) {
        set_field('user', 'emailstop', $mailstop, 'id', $user);
    }
    redirect($return, get_string('changessaved'));
}

if ($mailstop !== false) {
    list($in, $params) = $DB->get_in_or_equal($SESSION->bulk_users);
    $userlist = $DB->get_records_select_menu('user', "id $in", $params, 'fullname', 'id,'.$DB->sql_fullname().' AS fullname');
    $usernames = implode(', ', $userlist);
    $confstr = advuserbulk_get_string('confirm1', $pluginname);
    if ($mailstop == 0) {
        $confstr .= advuserbulk_get_string('activate', $pluginname);
    } else {
        $confstr .= advuserbulk_get_string('deactivate', $pluginname);
    }
    $confstr .= advuserbulk_get_string('confirm2', $pluginname) . '<br />';
    $confstr .= $usernames . '?';
    $optionsyes = array();
    $optionsyes['confirm'] = 1;
    $optionsyes['mailstop'] = $mailstop;
    $optionsyes['sesskey'] = sesskey();
    echo $OUTPUT->heading(get_string('confirmation', 'admin'));
    $formcontinue = new single_button(new moodle_url('index.php', array('confirm' => 1)), get_string('yes'));
    $formcancel = new single_button(new moodle_url($return), get_string('no'), 'get');
    echo $OUTPUT->confirm($confstr, $formcontinue, $formcancel);
} else {
?>
<div id="addmembersform" align=center>
    <form id="emailedform" method="post" action="index.php">
    <label for="mailstop"><?php echo advuserbulk_get_string('pluginname', $pluginname) ?></label>
    <br />
    <select name="mailstop" id="mailstop" size="1" >
        <option value=0><?php echo get_string('emailenable') ?></option>
        <option value=1><?php echo get_string('emaildisable') ?></option>
    </select>
    <br />
    <input type="submit" name="accept" value="<?php echo get_string( 'go' ) ?>" />
    </form>
</div>
<?php
}

echo $OUTPUT->footer();
?>
