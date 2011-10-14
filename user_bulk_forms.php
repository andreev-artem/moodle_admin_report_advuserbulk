<?php

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/datalib.php');
require_once($CFG->dirroot.'/'.$CFG->admin.'/report/advuserbulk/lib.php');

class user_bulk_action_form extends moodleform {
    function definition() {
        global $CFG;

        $mform =& $this->_form;

        $syscontext = get_context_instance(CONTEXT_SYSTEM);
        $actions = array();
        $plugins = get_list_of_plugins($CFG->admin.'/report/advuserbulk/actions', 'CVS');
        foreach ($plugins as $dir) {
            if (check_action_capabilities($dir)) {
                $actions[$dir] = advuserbulk_get_string('pluginname', ACTIONS_LANG_PREFIX.$dir);
            }
        }
        asort($actions);
        $actions = array_merge(array(0 => get_string('choose').'...'), $actions);

        $objs = array();
        $objs[] =& $mform->createElement('select', 'action', null, $actions);
        $objs[] =& $mform->createElement('submit', 'doaction', get_string('go'));
        $mform->addElement('group', 'actionsgrp', get_string('withselectedusers'), $objs, ' ', false);
    }
}

class user_bulk_form extends moodleform {
    function definition() {

        $mform =& $this->_form;
        $acount =& $this->_customdata['acount'];
        $scount =& $this->_customdata['scount'];
        $ausers =& $this->_customdata['ausers'];
        $susers =& $this->_customdata['susers'];
        $total  =& $this->_customdata['total'];

        $achoices = array();
        $schoices = array();

        if (is_array($ausers)) {
            if ($total == $acount) {
                $achoices[0] = get_string('allusers', 'bulkusers', $total);
            } else {
                $a = new object();
                $a->total  = $total;
                $a->count = $acount;
                $achoices[0] = get_string('allfilteredusers', 'bulkusers', $a);
            }
            $achoices = $achoices + $ausers;

            if ($acount > MAX_BULK_USERS) {
                $achoices[-1] = '...';
            }

        } else {
            $achoices[-1] = get_string('nofilteredusers', 'bulkusers', $total);
        }

        if (is_array($susers)) {
            $a = new object();
            $a->total  = $total;
            $a->count = $scount;
            $schoices[0] = get_string('allselectedusers', 'bulkusers', $a);
            $schoices = $schoices + $susers;

            if ($scount > MAX_BULK_USERS) {
                $schoices[-1] = '...';
            }

        } else {
            $schoices[-1] = get_string('noselectedusers', 'bulkusers');
        }

        $mform->addElement('header', 'users', get_string('usersinlist', 'bulkusers'));

        $objs = array();
        $objs[0] =& $mform->createElement('select', 'ausers', get_string('available', 'bulkusers'), $achoices, 'size="15"');
        $objs[0]->setMultiple(true);
        $objs[1] =& $mform->createElement('select', 'susers', get_string('selected', 'bulkusers'), $schoices, 'size="15"');
        $objs[1]->setMultiple(true);


        $mform->addElement('html', '<div style="white-space: nowrap;">');
        $grp =& $mform->addElement('group', 'usersgrp', get_string('users'), $objs, ' ', false);
        $mform->addElement('html', '</div>');
        $mform->addHelpButton('usersgrp', 'users', 'bulkusers');

        $mform->addElement('static', 'comment');

        $objs = array();
        $objs[] =& $mform->createElement('submit', 'addsel', get_string('addsel', 'bulkusers'));
        $objs[] =& $mform->createElement('submit', 'removesel', get_string('removesel', 'bulkusers'));
        $objs[] =& $mform->createElement('submit', 'addall', get_string('addall', 'bulkusers'));
        $objs[] =& $mform->createElement('submit', 'removeall', get_string('removeall', 'bulkusers'));
        $grp =& $mform->addElement('group', 'buttonsgrp', get_string('selectedlist', 'bulkusers'), $objs, array(' ', '<br />'), false);
        $mform->addHelpButton('buttonsgrp', 'selectedlist', 'bulkusers');

        $renderer =& $mform->defaultRenderer();
        $template = '<label class="qflabel" style="vertical-align:top">{label}</label> {element}';
        $renderer->setGroupElementTemplate($template, 'usersgrp');
    }
}
?>
