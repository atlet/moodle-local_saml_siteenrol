<?php
// This file is part of the SAML Site plugin for Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 2 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 * @package    local_saml_siteenrol
 * @copyright  2015, Andraž Prinčič <atletek@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */
require('../../config.php');
require_once($CFG->dirroot . '/local/saml_siteenrol/locallib.php');

$id = required_param('id', PARAM_INT);
$roleid = optional_param('roleid', -1, PARAM_INT);
$extendperiod = optional_param('extendperiod', 0, PARAM_INT);
$extendbase = optional_param('extendbase', 3, PARAM_INT);

require_login($id);
$context = context_course::instance($id, MUST_EXIST);

$canenrol = has_capability('local/saml_siteenrol:addorremoveusers', $context);

if (!$canenrol) {
    require_capability('local/saml_siteenrol:addorremoveusers', $context);
}

$instance = $DB->get_record('enrol', array('courseid'=>$id, 'enrol'=>'manual'), '*', MUST_EXIST);

$course = $DB->get_record('course', array('id' => $id));

$category = $DB->get_record('course_categories', array('id' =>$course->category));

$cats = array_reverse(array_filter(explode('/', $category->path)));

$rules = FALSE;

foreach ($cats as $cat) {
    $rules = $DB->get_record('local_saml_site', array('mdl_course_categories_id' => $cat));
    
    if ($rules) {
        break;
    }
}

$info = get_fast_modinfo($course);
//print_object($info);

if ($roleid < 0) {
    $roleid = $instance->roleid;
}
$roles = get_assignable_roles($context);
$roles = array('0' => get_string('none')) + $roles;

if (!isset($roles[$roleid])) {
    $roleid = 0;
}

if (!$enrol_manual = enrol_get_plugin('manual')) {
    throw new coding_exception('Can not instantiate enrol_manual');
}

$instancename = $enrol_manual->get_instance_name($instance);

$PAGE->set_url('/local/saml_siteenrol/usermanager.php', array('id' => $id));
$PAGE->set_pagelayout('admin');
$PAGE->set_title($enrol_manual->get_instance_name($instance));
$PAGE->set_heading($course->fullname);
navigation_node::override_active_url(new moodle_url('/local/saml_siteenrol/usermanager.php', array('id' => $id)));

// Create the user selector objects.
$options = array('enrolid' => $instance->id, 'accesscontext' => $context, 'rules' => $rules);

$potentialuserselector = new enrol_manual_potential_participant('addselect', $options);
$currentuserselector = new enrol_manual_current_participant('removeselect', $options);

// Build the list of options for the enrolment period dropdown.
$unlimitedperiod = get_string('unlimited');
$periodmenu = array();
for ($i = 1; $i <= 365; $i++) {
    $seconds = $i * 86400;
    $periodmenu[$seconds] = get_string('numdays', '', $i);
}
// Work out the apropriate default setting.
if ($extendperiod) {
    $defaultperiod = $extendperiod;
} else {
    $defaultperiod = $instance->enrolperiod;
}

// Build the list of options for the starting from dropdown.
$timeformat = get_string('strftimedatefullshort');
$today = time();
$today = make_timestamp(date('Y', $today), date('m', $today), date('d', $today), 0, 0, 0);

// Enrolment start.
$basemenu = array();
if ($course->startdate > 0) {
    $basemenu[2] = get_string('coursestart') . ' (' . userdate($course->startdate, $timeformat) . ')';
}
$basemenu[3] = get_string('today') . ' (' . userdate($today, $timeformat) . ')';

// Process add and removes.
if ($canenrol && optional_param('add', false, PARAM_BOOL) && confirm_sesskey()) {
    $userstoassign = $potentialuserselector->get_selected_users();
    if (!empty($userstoassign)) {
        foreach ($userstoassign as $adduser) {
            switch ($extendbase) {
                case 2:
                    $timestart = $course->startdate;
                    break;
                case 3:
                default:
                    $timestart = $today;
                    break;
            }

            if ($extendperiod <= 0) {
                $timeend = 0;
            } else {
                $timeend = $timestart + $extendperiod;
            }
            $enrol_manual->enrol_user($instance, $adduser->id, $roleid, $timestart, $timeend);
        }

        $potentialuserselector->invalidate_selected_users();
        $currentuserselector->invalidate_selected_users();

        //TODO: log
    }
}

// Process incoming role unassignments.
if (optional_param('remove', false, PARAM_BOOL) && confirm_sesskey()) {
    $userstounassign = $currentuserselector->get_selected_users();
    if (!empty($userstounassign)) {
        foreach ($userstounassign as $removeuser) {
            $enrol_manual->unenrol_user($instance, $removeuser->id);
        }

        $potentialuserselector->invalidate_selected_users();
        $currentuserselector->invalidate_selected_users();

        //TODO: log
    }
}


echo $OUTPUT->header();
echo $OUTPUT->heading($instancename);
if ($rules) {
?>
<form id="assignform" method="post" action="<?php echo $PAGE->url ?>"><div>
        <input type="hidden" name="sesskey" value="<?php echo sesskey() ?>" />

        <table summary="" class="roleassigntable generaltable generalbox boxaligncenter" cellspacing="0">
            <tr>
                <td id="existingcell">
                    <p><label for="removeselect"><?php print_string('enrolledusers', 'enrol'); ?></label></p>
                    <?php $currentuserselector->display() ?>
                </td>
                <td id="buttonscell">
                    <div id="addcontrols">
                        <input name="add" id="add" type="submit" value="<?php echo $OUTPUT->larrow() . '&nbsp;' . get_string('add'); ?>" title="<?php print_string('add'); ?>" /><br />

                        <div class="enroloptions">

                            <p><label for="menuroleid"><?php print_string('assignrole', 'enrol_manual') ?></label><br />
                                <?php
                                echo html_writer::select($roles, 'roleid', $roleid, false);
                                ?></p>

                            <p><label for="menuextendperiod"><?php print_string('enrolperiod', 'enrol') ?></label><br />
                                <?php
                                echo html_writer::select($periodmenu, 'extendperiod', $defaultperiod, $unlimitedperiod);
                                ?></p>

                            <p><label for="menuextendbase"><?php print_string('startingfrom') ?></label><br />
                                <?php
                                echo html_writer::select($basemenu, 'extendbase', $extendbase, false);
                                ?></p>

                        </div>
                    </div>

                    <div id="removecontrols">
                        <input name="remove" id="remove" type="submit" value="<?php echo get_string('remove') . '&nbsp;' . $OUTPUT->rarrow(); ?>" title="<?php print_string('remove'); ?>" />
                    </div>
                </td>
                <td id="potentialcell">
                    <p><label for="addselect"><?php print_string('enrolcandidates', 'enrol'); ?></label></p>
                    <?php $potentialuserselector->display() ?>
                </td>
            </tr>
        </table>
    </div></form>
<?php
} else {
    echo "<p>" . get_string('setupfilter', 'local_saml_siteenrol') . "</p>";
}
echo $OUTPUT->footer();
