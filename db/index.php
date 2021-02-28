<?php


// This file is part of X5Moodle (X5GON Activity plugin for Moodle)

// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**

  * X5GON Europeen project: AI based Recommendation System for Open Educative Resources
  * @package    mod_xfgon
  * @copyright  2018-2020 X5GON-Univ_Nantes_Team (https://chaireunescorel.ls2n.fr/, https://www.univ-nantes.fr/)
  * @license    BSD-2-Clause (https://opensource.org/licenses/BSD-2-Clause)

*/


/** Include required files */
require_once("../../config.php");
require_once($CFG->dirroot.'/mod/xfgon/locallib.php');

$id = required_param('id', PARAM_INT);   // course

$PAGE->set_url('/mod/xfgon/index.php', array('id'=>$id));

if (!$course = $DB->get_record("course", array("id" => $id))) {
    print_error('invalidcourseid');
}

require_login($course);
$PAGE->set_pagelayout('incourse');

// Trigger instances list viewed event.
$params = array(
    'context' => context_course::instance($course->id)
);
$event = \mod_xfgon\event\course_module_instance_list_viewed::create($params);
$event->add_record_snapshot('course', $course);
$event->trigger();

/// Get all required strings

$strxfgons = get_string("modulenameplural", "xfgon");
$strxfgon  = get_string("modulename", "xfgon");


/// Print the header
$PAGE->navbar->add($strxfgons);
$PAGE->set_title("$course->shortname: $strxfgons");
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading($strxfgons, 2);

/// Get all the appropriate data

if (! $xfgons = get_all_instances_in_course("xfgon", $course)) {
    notice(get_string('thereareno', 'moodle', $strxfgons), "../../course/view.php?id=$course->id");
    die;
}

$usesections = course_format_uses_sections($course->format);

/// Print the list of instances (your module will probably extend this)

$timenow = time();
$strname  = get_string("name");
$strgrade  = get_string("grade");
$strdeadline  = get_string("deadline", "xfgon");
$strnodeadline = get_string("nodeadline", "xfgon");
$table = new html_table();

if ($usesections) {
    $strsectionname = get_string('sectionname', 'format_'.$course->format);
    $table->head  = array ($strsectionname, $strname, $strgrade, $strdeadline);
    $table->align = array ("center", "left", "center", "center");
} else {
    $table->head  = array ($strname, $strgrade, $strdeadline);
    $table->align = array ("left", "center", "center");
}
// Get all deadlines.
$deadlines = xfgon_get_user_deadline($course->id);
foreach ($xfgons as $xfgon) {
    $cm = get_coursemodule_from_instance('xfgon', $xfgon->id);
    $context = context_module::instance($cm->id);

    $class = $xfgon->visible ? null : array('class' => 'dimmed'); // Hidden modules are dimmed.
    $link = html_writer::link(new moodle_url('view.php', array('id' => $cm->id)), format_string($xfgon->name, true), $class);

    $deadline = $deadlines[$xfgon->id]->userdeadline;
    if ($deadline == 0) {
        $due = $strnodeadline;
    } else if ($deadline > $timenow) {
        $due = userdate($deadline);
    } else {
        $due = html_writer::tag('span', userdate($deadline), array('class' => 'text-danger'));
    }

    if ($usesections) {
        if (has_capability('mod/xfgon:manage', $context)) {
            $grade_value = $xfgon->grade;
        } else {
            // it's a student, show their grade
            $grade_value = 0;
            if ($return = xfgon_get_user_grades($xfgon, $USER->id)) {
                $grade_value = $return[$USER->id]->rawgrade;
            }
        }
        $table->data[] = array (get_section_name($course, $xfgon->section), $link, $grade_value, $due);
    } else {
        $table->data[] = array ($link, $xfgon->grade, $due);
    }
}
echo html_writer::table($table);
echo $OUTPUT->footer();
