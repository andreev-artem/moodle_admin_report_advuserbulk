<?php
/**
 * user bulk action script for batch user enrolment
 */
require_once('../../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/' . $CFG->admin . '/report/advuserbulk/lib.php');

$allcourses     = optional_param('allcourses', '', PARAM_CLEAN);
$selcourses     = optional_param('selcourses', '', PARAM_CLEAN);
$accept         = optional_param('accept', 0, PARAM_BOOL);
$confirm        = optional_param('confirm', 0, PARAM_BOOL);
$cancel         = optional_param('cancel', 0, PARAM_BOOL);
$searchtext     = optional_param('searchtext', '', PARAM_RAW);
$showall        = optional_param('showall', 0, PARAM_BOOL);
$listadd        = optional_param('add', 0, PARAM_BOOL);
$listremove     = optional_param('remove', 0, PARAM_BOOL);
$removeall      = optional_param('removeall', 0, PARAM_BOOL);

admin_externalpage_setup('reportadvuserbulk');
check_action_capabilities('unenrolfromcourses', true);

$return = $CFG->wwwroot . '/' . $CFG->admin . '/report/advuserbulk/user_bulk.php';

if ($showall) {
    $searchtext = '';
}

$strsearch = get_string('search');
$pluginname = ACTIONS_LANG_PREFIX.'unenrolfromcourses';

if (empty($SESSION->bulk_users) || $cancel) {
    redirect($return);
}

if (!isset($SESSION->bulk_courses) || $removeall) {
    $SESSION->bulk_courses = array();
}

// course selection add/remove actions
if ($listadd && !empty($allcourses)) {
    foreach ($allcourses as $courseid) {
        if (!in_array($courseid, $SESSION->bulk_courses)) {
            $SESSION->bulk_courses[] = $courseid;
        }
    }
}

if ($listremove && !empty($selcourses)) {
    foreach ($selcourses as $courseid) {
        unset($SESSION->bulk_courses[array_search($courseid, $SESSION->bulk_courses)]);
    }
}

// show the confirmation message
if ($accept) {
    global $DB;

    if (empty($SESSION->bulk_courses)) {
        redirect($return);
    }

    // generate user name list
    list($in, $params) = $DB->get_in_or_equal($SESSION->bulk_users);
    $userlist = $DB->get_records_select_menu('user', "id $in", $params, 'fullname', 'id,' . $DB->sql_fullname() . ' AS fullname');
    $usernames = implode('<br />', $userlist);

    // generate course name list
    $courselist = array();
    $courses = get_courses('all', 'c.sortorder ASC', 'c.id, c.fullname');
    foreach ($courses as $course) {
        if (in_array($course->id, $SESSION->bulk_courses)) {
            $courselist[] = $course->fullname;
        }
    }

    // generate the message
    $confmsg = advuserbulk_get_string('confirmpart1', $pluginname) . '<b>' . $usernames . '</b>';
    $confmsg .= advuserbulk_get_string('confirmpart2', $pluginname);
    $confmsg .= '<b>' . implode('<br />', $courselist) . '</b>';

    $optionsyes['confirm'] = true;

    // print the message
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('confirmation', 'admin'));

    $buttonyes = new single_button(new moodle_url('index.php', $optionsyes), get_string('yes'));
    $buttonno = new single_button(new moodle_url($return), get_string('no'), 'get');
    echo $OUTPUT->confirm($confmsg, $buttonyes, $buttonno);

    echo $OUTPUT->footer();
    die;
}

// action confirmed, perform it
if ($confirm) {
    require_once($CFG->dirroot . '/group/lib.php');

    if (empty($SESSION->bulk_courses)) {
        redirect($return);
    }

    foreach ($SESSION->bulk_courses as $courseid) {
        $instances = enrol_get_instances($courseid, false);

        $plugins = array();
        foreach ($instances as $id => $instance) {
            if (!array_key_exists($instance->enrol, $plugins)) {
                $plugins[$instance->enrol] = (object)array(
                    'plugin' => enrol_get_plugin($instance->enrol),
                    'instances' => array($instance));
            }
            else {
                $plugins[$instance->enrol]->instances[] = $instance;
            }
        }

        foreach ($SESSION->bulk_users as $userid) {
            foreach ($plugins as $plugin) {
                foreach($plugin->instances as $instance) {
                    $plugin->plugin->unenrol_user($instance, $userid);
                }
            }
        }
    }

    redirect($return, get_string('changessaved'));
}

/**
 * This function generates the list of courses for <select> control
 * using the specified string filter and/or course id's filter
 *
 * @param string $strfilter The course name filter
 * @param array $arrayfilter Course ID's filter, NULL by default, which means not to use id filter
 * @return string
 */
function gen_course_list($strfilter = '', $arrayfilter = NULL, $filtinvert = false) {
    $courselist = array();
    $catcnt = 0;
    // get the list of course categories
    $categories = get_categories();
    foreach ($categories as $cat) {
        // for each category, add the <optgroup> to the string array first
        $courselist[$catcnt] = '<optgroup label="' . htmlspecialchars($cat->name) . '">';
        // get the course list in that category
        $courses = get_courses($cat->id, 'c.sortorder ASC', 'c.fullname, c.id');
        $coursecnt = 0;

        // for each course, check the specified filter
        foreach ($courses as $course) {
            if ((!empty($strfilter) && strripos($course->fullname, $strfilter) === false ) || ( $arrayfilter !== NULL && in_array($course->id, $arrayfilter) === $filtinvert )) {
                continue;
            }
            // if we pass the filter, add the option to the current string
            $courselist[$catcnt] .= '<option value="' . $course->id . '">' . $course->fullname . '</option>';
            $coursecnt++;
        }

        // if no courses pass the filter in that category, delete the current string
        if ($coursecnt == 0) {
            unset($courselist[$catcnt]);
        } else {
            $courselist[$catcnt] .= '</optgroup>';
            $catcnt++;
        }
    }

    // return the html code with categorized courses
    return implode(' ', $courselist);
}

// generate full and selected course lists
$availablecourses = array();
foreach ($SESSION->bulk_users as $user) {
    $usercourses = enrol_get_users_courses($user);
    foreach($usercourses as $key=>$junk) {
        $availablecourses[$key] = 0;
    }
}
$availablecourses = array_keys($availablecourses);

$coursenames = gen_course_list($searchtext, array_diff($availablecourses, $SESSION->bulk_courses));
$selcoursenames = gen_course_list('', array_intersect($availablecourses, $SESSION->bulk_courses));


// print the general page
echo $OUTPUT->header();
?>
<div id="addmembersform">
    <h3 class="main"><?php echo advuserbulk_get_string('title', $pluginname) ?></h3>

    <form id="addform" method="post" action="index.php">
        <table cellpadding="6" class="selectcourses generaltable generalbox boxaligncenter" summary="">
            <tr>
              <td id="existingcell">
                    <p>
                        <label for="allcourses"><?php echo advuserbulk_get_string('allcourses', $pluginname) ?></label>
                    </p>
                    <select name="allcourses[]" size="20" id="allcourses" multiple="multiple"
                            onfocus="document.getElementById('addform').add.disabled=false;
                                document.getElementById('addform').remove.disabled=true;
                                document.getElementById('addform').selcourses.selectedIndex=-1;"
                            onclick="this.focus();">
                                <?php echo $coursenames ?>
                    </select>

                    <br />
                    <label for="searchtext" class="accesshide"><?php p($strsearch) ?></label>
                    <input type="text" name="searchtext" id="searchtext" size="21" value="<?php p($searchtext, true) ?>"
                           onfocus ="getElementById('addform').add.disabled=true;
                               getElementById('addform').remove.disabled=true;
                               getElementById('addform').allcourses.selectedIndex=-1;
                               getElementById('addform').selcourses.selectedIndex=-1;"
                           onkeydown = "var keyCode = event.which ? event.which : event.keyCode;
                               if (keyCode == 13) {
                                   getElementById('addform').previoussearch.value=1;
                                   getElementById('addform').submit();
                               } " />
                    <input name="search" id="search" type="submit" value="<?php p($strsearch) ?>" />
                    <?php
                        if (!empty($searchtext)) {
                            echo '<br /><input name="showall" id="showall" type="submit" value="' . get_string('showall') . '" />' . "\n";
                        }
                    ?>
                </td>
              <td id="buttonscell">
                  <div id="addcontrols">
                        <input name="add" id="add" type="submit" disabled value="<?php echo '&nbsp;' . $OUTPUT->rarrow() . ' &nbsp; &nbsp; ' . get_string('add'); ?>" title="<?php print_string('add'); ?>" />
                  </div>
                  <div id="removecontrols">
                        <input name="remove" id="remove" type="submit" disabled value="<?php echo '&nbsp; ' . $OUTPUT->larrow() . ' &nbsp; &nbsp; ' . get_string('remove'); ?>" title="<?php print_string('remove'); ?>" />
                  </div>
                 </td>
          <td id="potentialcell">
                     <p>
                         <label for="selcourses"><?php echo advuserbulk_get_string('selectedcourses', $pluginname) ?></label>
                     </p>
                     <select name="selcourses[]" size="20" id="selcourses" multiple="multiple"
                             onfocus="document.getElementById('addform').remove.disabled=false;
                                      document.getElementById('addform').add.disabled=true;
                                      document.getElementById('addform').allcourses.selectedIndex=-1;"
                             onclick="this.focus();">
                            <?php echo $selcoursenames; ?>
                    </select>
                    <br />
                    <input name="removeall" id="removeall" type="submit" value="<?php echo get_string('removeall', 'bulkusers') ?>" />
                </td>
                </tr>
                <tr><td></td><td align="center">
                        <p><input type="submit" name="cancel" value="<?php echo get_string('cancel') ?>" />
                            <input type="submit" name="accept" value="<?php echo advuserbulk_get_string('accept', $pluginname) ?>" /></p>
                    </td>
                </tr>

        </table>
    </form>
</div>
<?php
    echo $OUTPUT->footer();
?>
