<?php

require_once($CFG->dirroot.'/user/filters/lib.php');

define('ACTIONS_LANG_PREFIX', 'bulkuseractions_');

if (!defined('MAX_BULK_USERS')) {
    define('MAX_BULK_USERS', 2000);
}

function add_selection_all($ufiltering) {
    global $SESSION, $DB, $CFG;

    list($sqlwhere, $params) = $ufiltering->get_sql_filter("id<>:exguest AND deleted <> 1", array('exguest'=>$CFG->siteguest));

    if ($rs = $DB->get_recordset_select('user', $sqlwhere, $params, 'fullname', 'id,'.$DB->sql_fullname().' AS fullname')) {
        foreach ($rs as $user) {
            if (!isset($SESSION->bulk_users[$user->id])) {
                $SESSION->bulk_users[$user->id] = $user->id;
            }
        }
        $rs->close();
    }
}

function get_selection_data($ufiltering) {
    global $SESSION, $DB, $CFG;

    // get the SQL filter
    list($sqlwhere, $params) = $ufiltering->get_sql_filter("id<>:exguest AND deleted <> 1", array('exguest'=>$CFG->siteguest));

    $total  = $DB->count_records_select('user', "id<>:exguest AND deleted <> 1", array('exguest'=>$CFG->siteguest));
    $acount = $DB->count_records_select('user', $sqlwhere, $params);
    $scount = count($SESSION->bulk_users);

    $userlist = array('acount'=>$acount, 'scount'=>$scount, 'ausers'=>false, 'susers'=>false, 'total'=>$total);
    $userlist['ausers'] = $DB->get_records_select_menu('user', $sqlwhere, $params, 'fullname', 'id,'.$DB->sql_fullname().' AS fullname', 0, MAX_BULK_USERS);

    if ($scount) {
        if ($scount < MAX_BULK_USERS) {
            $in = implode(',', $SESSION->bulk_users);
        } else {
            $bulkusers = array_slice($SESSION->bulk_users, 0, MAX_BULK_USERS, true);
            $in = implode(',', $bulkusers);
        }
        $userlist['susers'] = $DB->get_records_select_menu('user', "id IN ($in)", null, 'fullname', 'id,'.$DB->sql_fullname().' AS fullname');
    }

    return $userlist;
}


function check_action_capabilities($action, $require = false) {
    global $CFG;
    $requirecapability = NULL;
    if (file_exists($CFG->dirroot.'/'.$CFG->admin.'/report/advuserbulk/actions/'.$action.'/settings.php')) {
        include($CFG->dirroot.'/'.$CFG->admin.'/report/advuserbulk/actions/'.$action.'/settings.php');
    }

    if (is_null($requirecapability)) {
        if ($require) {
            print_error('action_nocaps');
        }
        return false;
    } else if (is_string($requirecapability)) {
        $caps = array( $requirecapability );
    } else if (is_array($requirecapability)) {
        $caps = $requirecapability;
    } else {
        if ($require) {
            print_error('action_nocaps');
        }
        return false;
    }
    
    $syscontext = get_context_instance(CONTEXT_SYSTEM);

    foreach ($caps as $cap) {
        if ($require) {
            require_capability($cap, $syscontext);
        } else {
            if (!has_capability($cap, $syscontext)) {
                return false;
            }
        }
    }
    
    return true;
}

function advuserbulk_get_string($identifier, $component, $a = NULL) {
    global $CFG;

    $identifier = clean_param($identifier, PARAM_STRINGID);
    if (empty($identifier)) {
        throw new coding_exception('Invalid string identifier. Most probably some illegal character is part of the string identifier. Please fix your get_string() call and string definition');
    }

    if (empty($component)) {
        throw new coding_exception('Parameter \'component\' for function advuserbulk_get_string() can not be empty. ');
    }

    if (strpos($component, ACTIONS_LANG_PREFIX) !== 0) {
        throw new coding_exception('Function advuserbulk_get_string() must be called only for actions strings (component \''.ACTIONS_LANG_PREFIX.'XXX\')');
    }

    $dir = substr($component, strlen(ACTIONS_LANG_PREFIX));

    $lang = current_language();

    $string = array();
    
    if (file_exists("$CFG->dirroot/$CFG->admin/report/advuserbulk/actions/$dir/lang/$lang/$component.php")) {
        include("$CFG->dirroot/$CFG->admin/report/advuserbulk/actions/$dir/lang/$lang/$component.php");
    } elseif (file_exists("$CFG->dirroot/$CFG->admin/report/advuserbulk/actions/$dir/lang/en/$component.php")) {
        include("$CFG->dirroot/$CFG->admin/report/advuserbulk/actions/$dir/lang/en/$component.php");
    } else {
        return "[[$identifier]]";
    }

    $string = $string[$identifier];

    if ($a !== NULL) {
        if (is_object($a) or is_array($a)) {
            $a = (array)$a;
            $search = array();
            $replace = array();
            foreach ($a as $key=>$value) {
                if (is_int($key)) {
                    // we do not support numeric keys - sorry!
                    continue;
                }
                $search[]  = '{$a->'.$key.'}';
                $replace[] = (string)$value;
            }
            if ($search) {
                $string = str_replace($search, $replace, $string);
            }
        } else {
            $string = str_replace('{$a}', (string)$a, $string);
        }
    }

    return $string;
}

?>
