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

use mod_xfgon\injector;

require_once(__DIR__.'/../../config.php');
require_once('lib/lib.php');


/** Actvityplugin part: xfgon activity **/
// Event types.
define('xfgon_EVENT_TYPE_OPEN', 'open');
define('xfgon_EVENT_TYPE_CLOSE', 'close');
/* Do not include any libraries here! */

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @global object
 * @global object
 * @param object $xfgon Lesson post data from the form
 * @return int
 **/
function xfgon_add_instance($data, $mform) {
    global $DB;
    $cmid = $data->coursemodule;
    $context = context_module::instance($cmid);

    xfgon_process_pre_save($data);

    $xfgonid = $DB->insert_record("xfgon", $data);
    $data->id = $xfgonid;


    xfgon_process_post_save($data);

    // post_process_addition: init x5features
    init_x5features($data);

    return $xfgonid;
}

function init_x5features($xfgon){

    // Init x5discovery feature
    init_x5discovery($xfgon);
}

function init_x5discovery($xfgon){

  global $COURSE;
  global $DB;

  $plugin_configs = get_config('mod_xfgon');
  $provider_token = 'x5gonPartnerToken';
  if( property_exists($plugin_configs,'providertoken') and $plugin_configs->providertoken!="" )  {
      $provider_token = $plugin_configs->providertoken;
  }

  $cmid = $xfgon->coursemodule;
  $crs_infos = get_needed_crs_infos();
  $cm = json_decode($crs_infos->course_modules)->$cmid;
  $cgi = json_decode($crs_infos->course_gen_infos);
  $cci = json_decode($crs_infos->course_cat_infos);


  foreach (explode(",", trim(trim($xfgon->x5discinit), ",")) as $key => $value) {

        $freaccess = json_encode((object) array(
            'fre'     => 'x5discovery',
            'acttype' => 'search',
            'actdata' => (object) array(
              'x5dq'    => trim($value)
            ),
            'timestamp'=> ((new DateTime())->getTimestamp())
        ));
        $mdlResourceNorm= array(
              'x5gonValidated'=> 'true',
              'dt'            => gmdate('Y-m-d\TH:i:s\Z', time()),
              'rq'            => $_SERVER['HTTP_REFERER'],
              'rf'            => $GLOBALS['GLBMDL_CFG']->wwwroot."/mod/".$xfgon->modulename."/view.php?id=".$cmid,
              'cid'           => $provider_token,
              'providertype'  => 'moodle',
              'title'         => $xfgon->name,
              'description'   => $xfgon->intro,
              'author'        => '',
              'language'      => $cgi->lang,
              'creation_date' => $cm->course_module_geninfos->added,
              'type'          => $xfgon->modulename,
              'mimetype'      => $xfgon->modulename,
              'license'       => '',
              'licensename'   => '',
              'resurl'        => $GLOBALS['GLBMDL_CFG']->wwwroot."/mod/".$xfgon->modulename."/view.php?id=".$cmid,
              'resmdlid'      => (string) $xfgon->id,
              'residinhtml'   => '',
              'resurlinpage'  => '',
              'rescrstitle'   => 'crs',
              'rescrsid'      => (string) $COURSE->id,
              'rescrsurl'     => $GLOBALS['GLBMDL_CFG']->wwwroot."/course/view.php?id=".$COURSE->id,
              'rescrssum'     => $cgi->summary,
              'rescrslang'    => $cgi->lang,
              'rescrsctg'     => $cci->name,
              'rescrsctgdesc' => $cci->description,
              'mdaccess'      => 'no',
              'mdaction'      => '',
              'mdsrc'         => '',
              'mdduration'    => '',
              'mdactiontime'  => '',
              "freacs"        => $freaccess
        );

        send_post_request("https://wp3.x5gon.org/others/moodle/x5plgn/freacs",
                          $mdlResourceNorm);


  }
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @global object
 * @param object $xfgon Lesson post data from the form
 * @return boolean
 **/
function xfgon_update_instance($data, $mform) {
    global $DB;

    $data->id = $data->instance;
    $cmid = $data->coursemodule;
    $context = context_module::instance($cmid);

    // useful: for pre-treatments before save
    xfgon_process_pre_save($data);

    $DB->update_record("xfgon", $data);

    // useful: for post-treatments like storing/launching events
    xfgon_process_post_save($data);


    return true;
}

/**
 * This function updates the events associated to the xfgon.
 * If $override is non-zero, then it updates only the events
 * associated with the specified override.
 *
 * @uses xfgon_MAX_EVENT_LENGTH
 * @param object $xfgon the xfgon object.
 * @param object $override (optional) limit to a specific override
 */
function xfgon_update_events($xfgon, $override = null) {
    global $CFG, $DB;

    require_once($CFG->dirroot . '/mod/xfgon/locallib.php');
    require_once($CFG->dirroot . '/calendar/lib.php');

    // Load the old events relating to this xfgon.
    $conds = array('modulename' => 'xfgon',
                   'instance' => $xfgon->id);
    if (!empty($override)) {
        // Only load events for this override.
        if (isset($override->userid)) {
            $conds['userid'] = $override->userid;
        } else {
            $conds['groupid'] = $override->groupid;
        }
    }
    $oldevents = $DB->get_records('event', $conds, 'id ASC');

    // Get group override priorities.
    $grouppriorities = xfgon_get_group_override_priorities($xfgon->id);


    // Delete any leftover events.
    foreach ($oldevents as $badevent) {
        $badevent = calendar_event::load($badevent);
        $badevent->delete();
    }
}

/**
 * Calculates the priorities of timeopen and timeclose values for group overrides for a xfgon.
 *
 * @param int $xfgonid The xfgon ID.
 * @return array|null Array of group override priorities for open and close times. Null if there are no group overrides.
 */
function xfgon_get_group_override_priorities($xfgonid) {
    global $DB;

    // Fetch group overrides.
    $where = 'xfgonid = :xfgonid AND groupid IS NOT NULL';
    $params = ['xfgonid' => $xfgonid];

    $grouptimeopen = [];
    $grouptimeclose = [];

    // Sort open times in ascending manner. The earlier open time gets higher priority.
    sort($grouptimeopen);
    // Set priorities.
    $opengrouppriorities = [];
    $openpriority = 1;
    foreach ($grouptimeopen as $timeopen) {
        $opengrouppriorities[$timeopen] = $openpriority++;
    }

    // Sort close times in descending manner. The later close time gets higher priority.
    rsort($grouptimeclose);
    // Set priorities.
    $closegrouppriorities = [];
    $closepriority = 1;
    foreach ($grouptimeclose as $timeclose) {
        $closegrouppriorities[$timeclose] = $closepriority++;
    }

    return [
        'open' => $opengrouppriorities,
        'close' => $closegrouppriorities
    ];
}

/**
 * This standard function will check all instances of this module
 * and make sure there are up-to-date events created for each of them.
 * If courseid = 0, then every xfgon event in the site is checked, else
 * only xfgon events belonging to the course specified are checked.
 * This function is used, in its new format, by restore_refresh_events()
 *
 * @param int $courseid
 * @param int|stdClass $instance Lesson module instance or ID.
 * @param int|stdClass $cm Course module object or ID (not used in this module).
 * @return bool
 */
function xfgon_refresh_events($courseid = 0, $instance = null, $cm = null) {
    global $DB;

    // If we have instance information then we can just update the one event instead of updating all events.
    if (isset($instance)) {
        if (!is_object($instance)) {
            $instance = $DB->get_record('xfgon', array('id' => $instance), '*', MUST_EXIST);
        }
        xfgon_update_events($instance);
        return true;
    }

    if ($courseid == 0) {
        if (!$xfgons = $DB->get_records('xfgon')) {
            return true;
        }
    } else {
        if (!$xfgons = $DB->get_records('xfgon', array('course' => $courseid))) {
            return true;
        }
    }

    foreach ($xfgons as $xfgon) {
        xfgon_update_events($xfgon);
    }

    return true;
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @global object
 * @param int $id
 * @return bool
 */
function xfgon_delete_instance($id) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/mod/xfgon/locallib.php');

    $xfgon = $DB->get_record("xfgon", array("id"=>$id), '*', MUST_EXIST);
    $xfgon = new xfgon($xfgon);
    return $xfgon->delete();
}

/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @global object
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $xfgon
 * @return object
 */
function xfgon_user_outline($course, $user, $mod, $xfgon) {
    global $CFG, $DB;

    require_once("$CFG->libdir/gradelib.php");
    $grades = grade_get_grades($course->id, 'mod', 'xfgon', $xfgon->id, $user->id);
    $return = new stdClass();

    if (empty($grades->items[0]->grades)) {
        $return->info = get_string("noxfgonattempts", "xfgon");
    } else {
        $grade = reset($grades->items[0]->grades);
        if (empty($grade->grade)) {

            // Check to see if it an ungraded / incomplete attempt.
            $sql = "SELECT *
                      FROM {xfgon_timer}
                     WHERE xfgonid = :xfgonid
                       AND userid = :userid
                  ORDER BY starttime DESC";
            $params = array('xfgonid' => $xfgon->id, 'userid' => $user->id);

            if ($attempts = $DB->get_records_sql($sql, $params, 0, 1)) {
                $attempt = reset($attempts);
                if ($attempt->completed) {
                    $return->info = get_string("completed", "xfgon");
                } else {
                    $return->info = get_string("notyetcompleted", "xfgon");
                }
                $return->time = $attempt->xfgontime;
            } else {
                $return->info = get_string("noxfgonattempts", "xfgon");
            }
        } else {
            if (!$grade->hidden || has_capability('moodle/grade:viewhidden', context_course::instance($course->id))) {
                $return->info = get_string('grade') . ': ' . $grade->str_long_grade;
            } else {
                $return->info = get_string('grade') . ': ' . get_string('hidden', 'grades');
            }

            // Datesubmitted == time created. dategraded == time modified or time overridden.
            // If grade was last modified by the user themselves use date graded. Otherwise use date submitted.
            // TODO: move this copied & pasted code somewhere in the grades API. See MDL-26704.
            if ($grade->usermodified == $user->id || empty($grade->datesubmitted)) {
                $return->time = $grade->dategraded;
            } else {
                $return->time = $grade->datesubmitted;
            }
        }
    }
    return $return;
}

/**
 * Print a detailed representation of what a  user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @global object
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $xfgon
 * @return bool
 */
function xfgon_user_complete($course, $user, $mod, $xfgon) {
    global $DB, $OUTPUT, $CFG;

    require_once("$CFG->libdir/gradelib.php");

    $grades = grade_get_grades($course->id, 'mod', 'xfgon', $xfgon->id, $user->id);

    // Display the grade and feedback.
    if (empty($grades->items[0]->grades)) {
        echo $OUTPUT->container(get_string("noxfgonattempts", "xfgon"));
    } else {
        $grade = reset($grades->items[0]->grades);
        if (empty($grade->grade)) {
            // Check to see if it an ungraded / incomplete attempt.
            $sql = "SELECT *
                      FROM {xfgon_timer}
                     WHERE xfgonid = :xfgonid
                       AND userid = :userid
                     ORDER by starttime desc";
            $params = array('xfgonid' => $xfgon->id, 'userid' => $user->id);

            if ($attempt = $DB->get_record_sql($sql, $params, IGNORE_MULTIPLE)) {
                if ($attempt->completed) {
                    $status = get_string("completed", "xfgon");
                } else {
                    $status = get_string("notyetcompleted", "xfgon");
                }
            } else {
                $status = get_string("noxfgonattempts", "xfgon");
            }
        } else {
            if (!$grade->hidden || has_capability('moodle/grade:viewhidden', context_course::instance($course->id))) {
                $status = get_string("grade") . ': ' . $grade->str_long_grade;
            } else {
                $status = get_string('grade') . ': ' . get_string('hidden', 'grades');
            }
        }

        // Display the grade or xfgon status if there isn't one.
        echo $OUTPUT->container($status);

        if ($grade->str_feedback &&
            (!$grade->hidden || has_capability('moodle/grade:viewhidden', context_course::instance($course->id)))) {
            echo $OUTPUT->container(get_string('feedback').': '.$grade->str_feedback);
        }
    }

    // Display the xfgon progress.
    // Attempt, pages viewed, questions answered, correct answers, time.
    $params = array ("xfgonid" => $xfgon->id, "userid" => $user->id);

    return true;
}

/**
 * Prints xfgon summaries on MyMoodle Page
 *
 * Prints xfgon name, due date and attempt information on
 * xfgons that have a deadline that has not already passed
 * and it is available for taking.
 *
 * @deprecated since 3.3
 * @todo The final deprecation of this function will take place in Moodle 3.7 - see MDL-57487.
 * @global object
 * @global stdClass
 * @global object
 * @uses CONTEXT_MODULE
 * @param array $courses An array of course objects to get xfgon instances from
 * @param array $htmlarray Store overview output array( course ID => 'xfgon' => HTML output )
 * @return void
 */
function xfgon_print_overview($courses, &$htmlarray) {
    global $USER, $CFG, $DB, $OUTPUT;

    debugging('The function xfgon_print_overview() is now deprecated.', DEBUG_DEVELOPER);

    if (!$xfgons = get_all_instances_in_courses('xfgon', $courses)) {
        return;
    }

    // Get all of the current users attempts on all xfgons.
    $params = array($USER->id);
    $sql = 'SELECT xfgonid, userid, count(userid) as attempts
              FROM {xfgon_grades}
             WHERE userid = ?
          GROUP BY xfgonid, userid';
    $allattempts = $DB->get_records_sql($sql, $params);
    $completedattempts = array();
    foreach ($allattempts as $myattempt) {
        $completedattempts[$myattempt->xfgonid] = $myattempt->attempts;
    }

    // Get the current course ID.
    $listofxfgons = array();
    foreach ($xfgons as $xfgon) {
        $listofxfgons[] = $xfgon->id;
    }
    // Get the last page viewed by the current user for every xfgon in this course.
    list($insql, $inparams) = $DB->get_in_or_equal($listofxfgons, SQL_PARAMS_NAMED);
    $dbparams = array_merge($inparams, array('userid' => $USER->id));

    // Get the xfgon attempts for the user that have the maximum 'timeseen' value.
    $select = "SELECT l.id, l.timeseen, l.xfgonid, l.userid, l.retry, l.pageid, l.answerid as nextpageid, p.qtype ";
    $from = "FROM {xfgon_attempts} l
             JOIN (
                   SELECT idselect.xfgonid, idselect.userid, MAX(idselect.id) AS id
                     FROM {xfgon_attempts} idselect
                     JOIN (
                           SELECT xfgonid, userid, MAX(timeseen) AS timeseen
                             FROM {xfgon_attempts}
                            WHERE userid = :userid
                              AND xfgonid $insql
                         GROUP BY userid, xfgonid
                           ) timeselect
                       ON timeselect.timeseen = idselect.timeseen
                      AND timeselect.userid = idselect.userid
                      AND timeselect.xfgonid = idselect.xfgonid
                 GROUP BY idselect.userid, idselect.xfgonid
                   ) aid
               ON l.id = aid.id
             JOIN {xfgon_pages} p
               ON l.pageid = p.id ";
    $lastattempts = $DB->get_records_sql($select . $from, $dbparams);

    // Now, get the xfgon branches for the user that have the maximum 'timeseen' value.
    $select = "SELECT l.id, l.timeseen, l.xfgonid, l.userid, l.retry, l.pageid, l.nextpageid, p.qtype ";
    // $from = str_replace('{xfgon_attempts}', '{xfgon_branch}', $from);
    $lastbranches = $DB->get_records_sql($select . $from, $dbparams);

    $lastviewed = array();
    foreach ($lastattempts as $lastattempt) {
        $lastviewed[$lastattempt->xfgonid] = $lastattempt;
    }

    // Go through the branch times and record the 'timeseen' value if it doesn't exist
    // for the xfgon, or replace it if it exceeds the current recorded time.
    foreach ($lastbranches as $lastbranch) {
        if (!isset($lastviewed[$lastbranch->xfgonid])) {
            $lastviewed[$lastbranch->xfgonid] = $lastbranch;
        } else if ($lastviewed[$lastbranch->xfgonid]->timeseen < $lastbranch->timeseen) {
            $lastviewed[$lastbranch->xfgonid] = $lastbranch;
        }
    }

    // Since we have xfgons in this course, now include the constants we need.
    require_once($CFG->dirroot . '/mod/xfgon/locallib.php');

    $now = time();
    foreach ($xfgons as $xfgon) {
        if ($xfgon->deadline != 0                                         // The xfgon has a deadline
            and $xfgon->deadline >= $now                                  // And it is before the deadline has been met
            and ($xfgon->available == 0 or $xfgon->available <= $now)) { // And the xfgon is available

            // Visibility.
            $class = (!$xfgon->visible) ? 'dimmed' : '';

            // Context.
            $context = context_module::instance($xfgon->coursemodule);

            // Link to activity.
            $url = new moodle_url('/mod/xfgon/view.php', array('id' => $xfgon->coursemodule));
            $url = html_writer::link($url, format_string($xfgon->name, true, array('context' => $context)), array('class' => $class));
            $str = $OUTPUT->box(get_string('xfgonname', 'xfgon', $url), 'name');

            // Deadline.
            $str .= $OUTPUT->box(get_string('xfgoncloseson', 'xfgon', userdate($xfgon->deadline)), 'info');

            // Attempt information.
            if (has_capability('mod/xfgon:manage', $context)) {
                // This is a teacher, Get the Number of user attempts.
                // $attempts = $DB->count_records('xfgon_grades', array('xfgonid' => $xfgon->id));
                // $str     .= $OUTPUT->box(get_string('xattempts', 'xfgon', $attempts), 'info');
                // $str      = $OUTPUT->box($str, 'xfgon overview');
            } else {
                // This is a student, See if the user has at least started the xfgon.
                if (isset($lastviewed[$xfgon->id]->timeseen)) {
                    // See if the user has finished this attempt.
                    if (isset($completedattempts[$xfgon->id]) &&
                             ($completedattempts[$xfgon->id] == ($lastviewed[$xfgon->id]->retry + 1))) {
                        // Are additional attempts allowed?
                        if ($xfgon->retake) {
                            // User can retake the xfgon.
                            $str .= $OUTPUT->box(get_string('additionalattemptsremaining', 'xfgon'), 'info');
                            $str = $OUTPUT->box($str, 'xfgon overview');
                        } else {
                            // User has completed the xfgon and no retakes are allowed.
                            $str = '';
                        }

                    } else {
                        // The last attempt was not finished or the xfgon does not contain questions.
                        // See if the last page viewed was a branchtable.
                        require_once($CFG->dirroot . '/mod/xfgon/pagetypes/branchtable.php');
                        if ($lastviewed[$xfgon->id]->qtype == xfgon_PAGE_BRANCHTABLE) {
                            // See if the next pageid is the end of xfgon.
                            if ($lastviewed[$xfgon->id]->nextpageid == xfgon_EOL) {
                                // The last page viewed was the End of Lesson.
                                if ($xfgon->retake) {
                                    // User can retake the xfgon.
                                    $str .= $OUTPUT->box(get_string('additionalattemptsremaining', 'xfgon'), 'info');
                                    $str = $OUTPUT->box($str, 'xfgon overview');
                                } else {
                                    // User has completed the xfgon and no retakes are allowed.
                                    $str = '';
                                }

                            } else {
                                // The last page viewed was NOT the end of xfgon.
                                $str .= $OUTPUT->box(get_string('notyetcompleted', 'xfgon'), 'info');
                                $str = $OUTPUT->box($str, 'xfgon overview');
                            }

                        } else {
                            // Last page was a question page, so the attempt is not completed yet.
                            $str .= $OUTPUT->box(get_string('notyetcompleted', 'xfgon'), 'info');
                            $str = $OUTPUT->box($str, 'xfgon overview');
                        }
                    }

                } else {
                    // User has not yet started this xfgon.
                    $str .= $OUTPUT->box(get_string('noxfgonattempts', 'xfgon'), 'info');
                    $str = $OUTPUT->box($str, 'xfgon overview');
                }
            }
            if (!empty($str)) {
                if (empty($htmlarray[$xfgon->course]['xfgon'])) {
                    $htmlarray[$xfgon->course]['xfgon'] = $str;
                } else {
                    $htmlarray[$xfgon->course]['xfgon'] .= $str;
                }
            }
        }
    }
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 * @global stdClass
 * @return bool true
 */
function xfgon_cron () {
    global $CFG;

    return true;
}

/**
 * Return grade for given user or all users.
 *
 * @global stdClass
 * @global object
 * @param int $xfgonid id of xfgon
 * @param int $userid optional user id, 0 means all users
 * @return array array of grades, false if none
 */
function xfgon_get_user_grades($xfgon, $userid=0) {
    global $CFG, $DB;

    $params = array("xfgonid" => $xfgon->id,"xfgonid2" => $xfgon->id);

    if (!empty($userid)) {
        $params["userid"] = $userid;
        $params["userid2"] = $userid;
        $user = "AND u.id = :userid";
        $fuser = "AND uu.id = :userid2";
    }
    else {
        $user="";
        $fuser="";
    }

    if ($xfgon->retake) {
        if ($xfgon->usemaxgrade) {
            $sql = "SELECT u.id, u.id AS userid, MAX(g.grade) AS rawgrade
                      FROM {user} u, {xfgon_grades} g
                     WHERE u.id = g.userid AND g.xfgonid = :xfgonid
                           $user
                  GROUP BY u.id";
        } else {
            $sql = "SELECT u.id, u.id AS userid, AVG(g.grade) AS rawgrade
                      FROM {user} u, {xfgon_grades} g
                     WHERE u.id = g.userid AND g.xfgonid = :xfgonid
                           $user
                  GROUP BY u.id";
        }
        unset($params['xfgonid2']);
        unset($params['userid2']);
    } else {
        // use only first attempts (with lowest id in xfgon_grades table)
        $firstonly = "SELECT uu.id AS userid, MIN(gg.id) AS firstcompleted
                        FROM {user} uu, {xfgon_grades} gg
                       WHERE uu.id = gg.userid AND gg.xfgonid = :xfgonid2
                             $fuser
                       GROUP BY uu.id";

        $sql = "SELECT u.id, u.id AS userid, g.grade AS rawgrade
                  FROM {user} u, {xfgon_grades} g, ($firstonly) f
                 WHERE u.id = g.userid AND g.xfgonid = :xfgonid
                       AND g.id = f.firstcompleted AND g.userid=f.userid
                       $user";
    }

    return $DB->get_records_sql($sql, $params);
}

/**
 * Update grades in central gradebook
 *
 * @category grade
 * @param object $xfgon
 * @param int $userid specific user only, 0 means all
 * @param bool $nullifnone
 */
function xfgon_update_grades($xfgon, $userid=0, $nullifnone=true) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');

    if ($xfgon->grade == 0 || $xfgon->practice) {
        xfgon_grade_item_update($xfgon);

    } else if ($grades = xfgon_get_user_grades($xfgon, $userid)) {
        xfgon_grade_item_update($xfgon, $grades);

    } else if ($userid and $nullifnone) {
        $grade = new stdClass();
        $grade->userid   = $userid;
        $grade->rawgrade = null;
        xfgon_grade_item_update($xfgon, $grade);

    } else {
        xfgon_grade_item_update($xfgon);
    }
}

/**
 * Create grade item for given xfgon
 *
 * @category grade
 * @uses GRADE_TYPE_VALUE
 * @uses GRADE_TYPE_NONE
 * @param object $xfgon object with extra cmidnumber
 * @param array|object $grades optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 */
function xfgon_grade_item_update($xfgon, $grades=null) {
    global $CFG;
    if (!function_exists('grade_update')) { //workaround for buggy PHP versions
        require_once($CFG->libdir.'/gradelib.php');
    }

    if (array_key_exists('cmidnumber', $xfgon)) { //it may not be always present
        $params = array('itemname'=>$xfgon->name, 'idnumber'=>$xfgon->cmidnumber);
    } else {
        $params = array('itemname'=>$xfgon->name);
    }

    if ($grades  === 'reset') {
        $params['reset'] = true;
        $grades = null;
    } else if (!empty($grades)) {
        // Need to calculate raw grade (Note: $grades has many forms)
        if (is_object($grades)) {
            $grades = array($grades->userid => $grades);
        } else if (array_key_exists('userid', $grades)) {
            $grades = array($grades['userid'] => $grades);
        }
        foreach ($grades as $key => $grade) {
            if (!is_array($grade)) {
                $grades[$key] = $grade = (array) $grade;
            }
            //check raw grade isnt null otherwise we erroneously insert a grade of 0
            if ($grade['rawgrade'] !== null) {
                $grades[$key]['rawgrade'] = ($grade['rawgrade'] * $params['grademax'] / 100);
            } else {
                //setting rawgrade to null just in case user is deleting a grade
                $grades[$key]['rawgrade'] = null;
            }
        }
    }

    return grade_update('mod/xfgon', $xfgon->course, 'mod', 'xfgon', $xfgon->id, 0, $grades, $params);
}

/**
 * List the actions that correspond to a view of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = 'r' and edulevel = LEVEL_PARTICIPATING will
 *       be considered as view action.
 *
 * @return array
 */
function xfgon_get_view_actions() {
    return array('view','view all');
}

/**
 * List the actions that correspond to a post of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = ('c' || 'u' || 'd') and edulevel = LEVEL_PARTICIPATING
 *       will be considered as post action.
 *
 * @return array
 */
function xfgon_get_post_actions() {
    return array('end','start');
}

/**
 * Runs any processes that must run before
 * a xfgon insert/update
 *
 * @global object
 * @param object $xfgon Lesson form data
 * @return void
 **/
function xfgon_process_pre_save(&$xfgon) {
    global $DB;

    $xfgon->timemodified = time();

    if (empty($xfgon->timelimit)) {
        $xfgon->timelimit = 0;
    }
    if (empty($xfgon->timespent) or !is_numeric($xfgon->timespent) or $xfgon->timespent < 0) {
        $xfgon->timespent = 0;
    }
    if (!isset($xfgon->completed)) {
        $xfgon->completed = 0;
    }
    if (empty($xfgon->gradebetterthan) or !is_numeric($xfgon->gradebetterthan) or $xfgon->gradebetterthan < 0) {
        $xfgon->gradebetterthan = 0;
    } else if ($xfgon->gradebetterthan > 100) {
        $xfgon->gradebetterthan = 100;
    }

    if (empty($xfgon->width)) {
        $xfgon->width = 640;
    }
    if (empty($xfgon->height)) {
        $xfgon->height = 480;
    }
    if (empty($xfgon->bgcolor)) {
        $xfgon->bgcolor = '#FFFFFF';
    }

    // Conditions for dependency
    $conditions = new stdClass;
    $conditions->timespent = $xfgon->timespent;
    $conditions->completed = $xfgon->completed;
    $conditions->gradebetterthan = $xfgon->gradebetterthan;
    $xfgon->conditions = serialize($conditions);
    unset($xfgon->timespent);
    unset($xfgon->completed);
    unset($xfgon->gradebetterthan);

    if (empty($xfgon->password)) {
        unset($xfgon->password);
    }

    // Content settings (x5 fetaures and plst items...)
    xfgon_prepare_content_data($xfgon);
}

function xfgon_prepare_content_data(&$xfgon) {

    global $CFG;
    require_once($CFG->dirroot.'/mod/xfgon/locallib.php');

    $content_data = new stdClass();
    $content_data->enablex5discovery = "0";
    $content_data->x5discoverystgs = new stdClass();
    $content_data->x5discoverystgs->init = trim(trim($xfgon->x5discinit), ",") ;

    $content_data->enablex5recommend = "0";
    $content_data->x5recommendstgs = new stdClass();
    $content_data->x5recommendstgs->init = "" ;

    $content_data->enablex5playlist = "0";
    $content_data->x5playliststgs = new stdClass();
    $content_data->x5playliststgs->pst5lnftch = "pst5lnftchfmmbz";
    $content_data->x5playliststgs->pst5lnurl = 0;
    $content_data->x5playliststgs->plstln5mbzatmt = "";
    $content_data->x5playlistitems = [];
    $content_data->x5playlistitemstles = [];


    if ($xfgon->enablex5discovery != "0"){
      $content_data->enablex5discovery = "1";
    }
    if ($xfgon->enablex5recommend != "0"){
      $content_data->enablex5recommend = "1";
    }
    if ($xfgon->enablex5playlist != "0"){
      $content_data->enablex5playlist = "1";
    }
    if ( property_exists($xfgon, "pstit") ) {
      $content_data->x5playlistitems = $xfgon->pstit;
      $content_data->x5playlistitemstles = $xfgon->pstittle;
    }
    if ( property_exists($xfgon, "pst5lnftch") ) {
      $content_data->x5playliststgs->pst5lnftch = $xfgon->pst5lnftch ;
      $content_data->x5playliststgs->pst5lnurl = $xfgon->pst5lnurl ;
      $content_data->x5playliststgs->plstln5mbzatmt = $xfgon->plstln5mbzatmt ;

    }


    $xfgon->contentsettings = serialize($content_data);
    $xfgon->x5playlist = serialize(get_plst_items(array('pst5lnftchfmmbz' => $xfgon->plstln5mbzatmt,
                                                        'pst5lnftchfmurl'=>$xfgon->pst5lnurl,
                                                         'pst5lnftchmd' => $content_data->x5playliststgs->pst5lnftch)));
}

/**
 * Runs any processes that must be run
 * after a xfgon insert/update
 *
 * @global object
 * @param object $xfgon Lesson form data
 * @return void
 **/
function xfgon_process_post_save(&$xfgon) {
    // Update the events relating to this xfgon.
    xfgon_update_events($xfgon);
    $completionexpected = (!empty($xfgon->completionexpected)) ? $xfgon->completionexpected : null;
    \core_completion\api::update_completion_date_event($xfgon->coursemodule, 'xfgon', $xfgon, $completionexpected);
}


/**
 * Implementation of the function for printing the form elements that control
 * whether the course reset functionality affects the xfgon.
 *
 * @param $mform form passed by reference
 */
function xfgon_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'xfgonheader', get_string('modulenameplural', 'xfgon'));
    $mform->addElement('advcheckbox', 'reset_xfgon', get_string('deleteallattempts','xfgon'));
    $mform->addElement('advcheckbox', 'reset_xfgon_user_overrides',
            get_string('removealluseroverrides', 'xfgon'));
    $mform->addElement('advcheckbox', 'reset_xfgon_group_overrides',
            get_string('removeallgroupoverrides', 'xfgon'));
}

/**
 * Course reset form defaults.
 * @param object $course
 * @return array
 */
function xfgon_reset_course_form_defaults($course) {
    return array('reset_xfgon' => 1,
            'reset_xfgon_group_overrides' => 1,
            'reset_xfgon_user_overrides' => 1);
}

/**
 * Removes all grades from gradebook
 *
 * @global stdClass
 * @global object
 * @param int $courseid
 * @param string optional type
 */
function xfgon_reset_gradebook($courseid, $type='') {
    global $CFG, $DB;

    $sql = "SELECT l.*, cm.idnumber as cmidnumber, l.course as courseid
              FROM {xfgon} l, {course_modules} cm, {modules} m
             WHERE m.name='xfgon' AND m.id=cm.module AND cm.instance=l.id AND l.course=:course";
    $params = array ("course" => $courseid);
    if ($xfgons = $DB->get_records_sql($sql,$params)) {
        foreach ($xfgons as $xfgon) {
            xfgon_grade_item_update($xfgon, 'reset');
        }
    }
}

/**
 * Actual implementation of the reset course functionality, delete all the
 * xfgon attempts for course $data->courseid.
 *
 * @global stdClass
 * @global object
 * @param object $data the data submitted from the reset course.
 * @return array status array
 */
function xfgon_reset_userdata($data) {
    global $CFG, $DB;

    $componentstr = get_string('modulenameplural', 'xfgon');
    $status = array();

    if (!empty($data->reset_xfgon)) {
        $xfgonssql = "SELECT l.id
                         FROM {xfgon} l
                        WHERE l.course=:course";

        $params = array ("course" => $data->courseid);
        $xfgons = $DB->get_records_sql($xfgonssql, $params);

        // Get rid of attempts files.
        $fs = get_file_storage();
        if ($xfgons) {
            foreach ($xfgons as $xfgonid => $unused) {
                if (!$cm = get_coursemodule_from_instance('xfgon', $xfgonid)) {
                    continue;
                }
                $context = context_module::instance($cm->id);
                $fs->delete_area_files($context->id, 'mod_xfgon', 'essay_responses');
            }
        }

        // remove all grades from gradebook
        if (empty($data->reset_gradebook_grades)) {
            xfgon_reset_gradebook($data->courseid);
        }

        $status[] = array('component'=>$componentstr, 'item'=>get_string('deleteallattempts', 'xfgon'), 'error'=>false);
    }

    return $status;
}

/**
 * @uses FEATURE_GROUPS
 * @uses FEATURE_GROUPINGS
 * @uses FEATURE_MOD_INTRO
 * @uses FEATURE_COMPLETION_TRACKS_VIEWS
 * @uses FEATURE_GRADE_HAS_GRADE
 * @uses FEATURE_GRADE_OUTCOMES
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function xfgon_supports($feature) {
    switch($feature) {
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_GRADE_OUTCOMES:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        default:
            return null;
    }
}

/**
 * Obtains the automatic completion state for this xfgon based on any conditions
 * in xfgon settings.
 *
 * @param object $course Course
 * @param object $cm course-module
 * @param int $userid User ID
 * @param bool $type Type of comparison (or/and; can be used as return value if no conditions)
 * @return bool True if completed, false if not, $type if conditions not set.
 */
function xfgon_get_completion_state($course, $cm, $userid, $type) {
    global $CFG, $DB;

    // Get xfgon details.
    $xfgon = $DB->get_record('xfgon', array('id' => $cm->instance), '*',
            MUST_EXIST);

    $result = $type; // Default return value.

    if ($xfgon->completiontimespent != 0) {
        $duration = $DB->get_field_sql(
                        "SELECT SUM(xfgontime - starttime)
                               FROM {xfgon_timer}
                              WHERE xfgonid = :xfgonid
                                AND userid = :userid",
                        array('userid' => $userid, 'xfgonid' => $xfgon->id));
        if (!$duration) {
            $duration = 0;
        }
        if ($type == COMPLETION_AND) {
            $result = $result && ($xfgon->completiontimespent < $duration);
        } else {
            $result = $result || ($xfgon->completiontimespent < $duration);
        }
    }
    return $result;
}
/**
 * This function extends the settings navigation block for the site.
 *
 * It is safe to rely on PAGE here as we will only ever be within the module
 * context when this is called
 *
 * @param settings_navigation $settings
 * @param navigation_node $xfgonnode
 */
function xfgon_extend_settings_navigation($settings, $xfgonnode) {
    global $PAGE, $DB;

    // We want to add these new nodes after the Edit settings node, and before the
    // Locally assigned roles node. Of course, both of those are controlled by capabilities.
    $keys = $xfgonnode->get_children_key_list();
    $beforekey = null;
    $i = array_search('modedit', $keys);
    if ($i === false and array_key_exists(0, $keys)) {
        $beforekey = $keys[0];
    } else if (array_key_exists($i + 1, $keys)) {
        $beforekey = $keys[$i + 1];
    }


}

/**
 * Get list of available import or export formats
 *
 * Copied and modified from lib/questionlib.php
 *
 * @param string $type 'import' if import list, otherwise export list assumed
 * @return array sorted list of import/export formats available
 */
function xfgon_get_import_export_formats($type) {
    global $CFG;
    $fileformats = core_component::get_plugin_list("qformat");

    $fileformatname=array();
    foreach ($fileformats as $fileformat=>$fdir) {
        $format_file = "$fdir/format.php";
        if (file_exists($format_file) ) {
            require_once($format_file);
        } else {
            continue;
        }
        $classname = "qformat_$fileformat";
        $format_class = new $classname();
        if ($type=='import') {
            $provided = $format_class->provide_import();
        } else {
            $provided = $format_class->provide_export();
        }
        if ($provided) {
            $fileformatnames[$fileformat] = get_string('pluginname', 'qformat_'.$fileformat);
        }
    }
    natcasesort($fileformatnames);

    return $fileformatnames;
}

/**
 * Serves the xfgon attachments. Implements needed access control ;-)
 *
 * @package mod_xfgon
 * @category files
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - justsend the file
 */
function xfgon_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    global $CFG, $DB;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    $fileareas = xfgon_get_file_areas();
    if (!array_key_exists($filearea, $fileareas)) {
        return false;
    }

    if (!$xfgon = $DB->get_record('xfgon', array('id'=>$cm->instance))) {
        return false;
    }

    require_course_login($course, true, $cm);

    if ($filearea === 'page_contents') {

    } else if ($filearea === 'page_answers' || $filearea === 'page_responses') {
        $itemid = (int)array_shift($args);

    } else if ($filearea === 'essay_responses') {
        $itemid = (int)array_shift($args);

    } else if ($filearea === 'mediafile') {
        if (count($args) > 1) {
            // Remove the itemid when it appears to be part of the arguments. If there is only one argument
            // then it is surely the file name. The itemid is sometimes used to prevent browser caching.
            array_shift($args);
        }
        $fullpath = "/$context->id/mod_xfgon/$filearea/0/".implode('/', $args);

    } else {
        return false;
    }

    $fs = get_file_storage();
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }

    // finally send the file
    send_stored_file($file, 0, 0, $forcedownload, $options); // download MUST be forced - security!
}

/**
 * Returns an array of file areas
 *
 * @package  mod_xfgon
 * @category files
 * @return array a list of available file areas
 */
function xfgon_get_file_areas() {
    $areas = array();
    $areas['page_contents'] = get_string('pagecontents', 'mod_xfgon');
    $areas['mediafile'] = get_string('mediafile', 'mod_xfgon');
    $areas['page_answers'] = get_string('pageanswers', 'mod_xfgon');
    $areas['page_responses'] = get_string('pageresponses', 'mod_xfgon');
    $areas['essay_responses'] = get_string('essayresponses', 'mod_xfgon');
    return $areas;
}

/**
 * Returns a file_info_stored object for the file being requested here
 *
 * @package  mod_xfgon
 * @category files
 * @global stdClass $CFG
 * @param file_browse $browser file browser instance
 * @param array $areas file areas
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param int $itemid item ID
 * @param string $filepath file path
 * @param string $filename file name
 * @return file_info_stored
 */
function xfgon_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    global $CFG, $DB;

    if (!has_capability('moodle/course:managefiles', $context)) {
        // No peaking here for students!
        return null;
    }

    // Mediafile area does not have sub directories, so let's select the default itemid to prevent
    // the user from selecting a directory to access the mediafile content.
    if ($filearea == 'mediafile' && is_null($itemid)) {
        $itemid = 0;
    }

    if (is_null($itemid)) {
        return new mod_xfgon_file_info($browser, $course, $cm, $context, $areas, $filearea);
    }

    $fs = get_file_storage();
    $filepath = is_null($filepath) ? '/' : $filepath;
    $filename = is_null($filename) ? '.' : $filename;
    if (!$storedfile = $fs->get_file($context->id, 'mod_xfgon', $filearea, $itemid, $filepath, $filename)) {
        return null;
    }

    $itemname = $filearea;
    if ($filearea == 'page_contents') {
        // $itemname = $DB->get_field('xfgon_pages', 'title', array('xfgonid' => $cm->instance, 'id' => $itemid));
        // $itemname = format_string($itemname, true, array('context' => $context));
    } else {
        $areas = xfgon_get_file_areas();
        if (isset($areas[$filearea])) {
            $itemname = $areas[$filearea];
        }
    }

    $urlbase = $CFG->wwwroot . '/pluginfile.php';
    return new file_info_stored($browser, $context, $storedfile, $urlbase, $itemname, $itemid, true, true, false);
}


/**
 * Return a list of page types
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 */
function xfgon_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $module_pagetype = array(
        'mod-xfgon-*'=>get_string('page-mod-xfgon-x', 'xfgon'),
        'mod-xfgon-view'=>get_string('page-mod-xfgon-view', 'xfgon'),
        'mod-xfgon-edit'=>get_string('page-mod-xfgon-edit', 'xfgon'));
    return $module_pagetype;
}

/**
 * Update the xfgon activity to include any file
 * that was uploaded, or if there is none, set the
 * mediafile field to blank.
 *
 * @param int $xfgonid the xfgon id
 * @param stdClass $context the context
 * @param int $draftitemid the draft item
 */
function xfgon_update_media_file($xfgonid, $context, $draftitemid) {
    global $DB;

    // Set the filestorage object.
    $fs = get_file_storage();
    // Save the file if it exists that is currently in the draft area.
    file_save_draft_area_files($draftitemid, $context->id, 'mod_xfgon', 'mediafile', 0);
    // Get the file if it exists.
    $files = $fs->get_area_files($context->id, 'mod_xfgon', 'mediafile', 0, 'itemid, filepath, filename', false);
    // Check that there is a file to process.
    if (count($files) == 1) {
        // Get the first (and only) file.
        $file = reset($files);
        // Set the mediafile column in the xfgons table.
        $DB->set_field('xfgon', 'mediafile', '/' . $file->get_filename(), array('id' => $xfgonid));
    } else {
        // Set the mediafile column in the xfgons table.
        $DB->set_field('xfgon', 'mediafile', '', array('id' => $xfgonid));
    }
}

/**
 * Get icon mapping for font-awesome.
 */
function mod_xfgon_get_fontawesome_icon_map() {
    return [
        'mod_xfgon:e/copy' => 'fa-clone',
    ];
}

/*
 * Check if the module has any update that affects the current user since a given time.
 *
 * @param  cm_info $cm course module data
 * @param  int $from the time to check updates from
 * @param  array $filter  if we need to check only specific updates
 * @return stdClass an object with the different type of areas indicating if they were updated or not
 * @since Moodle 3.3
 */
function xfgon_check_updates_since(cm_info $cm, $from, $filter = array()) {
    global $DB, $USER;

    $updates = course_check_module_updates_since($cm, $from, array(), $filter);

    // Check if there are new pages or answers in the xfgon.
    $updates->pages = (object) array('updated' => false);
    $updates->answers = (object) array('updated' => false);
    $select = 'xfgonid = ? AND (timecreated > ? OR timemodified > ?)';
    $params = array($cm->instance, $from, $from);

    // Check for new question attempts, grades, pages viewed and timers.
    $updates->questionattempts = (object) array('updated' => false);
    $updates->grades = (object) array('updated' => false);
    $updates->pagesviewed = (object) array('updated' => false);
    $updates->timers = (object) array('updated' => false);

    $select = 'xfgonid = ? AND userid = ? AND timeseen > ?';
    $params = array($cm->instance, $USER->id, $from);

    $select = 'xfgonid = ? AND userid = ? AND completed > ?';

    $select = 'xfgonid = ? AND userid = ? AND (starttime > ? OR xfgontime > ? OR timemodifiedoffline > ?)';
    $params = array($cm->instance, $USER->id, $from, $from, $from);

    // Now, teachers should see other students updates.
    if (has_capability('mod/xfgon:viewreports', $cm->context)) {
        $select = 'xfgonid = ? AND timeseen > ?';
        $params = array($cm->instance, $from);

        $insql = '';
        $inparams = [];
        if (groups_get_activity_groupmode($cm) == SEPARATEGROUPS) {
            $groupusers = array_keys(groups_get_activity_shared_group_members($cm));
            if (empty($groupusers)) {
                return $updates;
            }
            list($insql, $inparams) = $DB->get_in_or_equal($groupusers);
            $select .= ' AND userid ' . $insql;
            $params = array_merge($params, $inparams);
        }

        $updates->userquestionattempts = (object) array('updated' => false);
        $updates->usergrades = (object) array('updated' => false);
        $updates->userpagesviewed = (object) array('updated' => false);
        $updates->usertimers = (object) array('updated' => false);


        $select = 'xfgonid = ? AND completed > ?';
        if (!empty($insql)) {
            $select .= ' AND userid ' . $insql;
        }

        $select = 'xfgonid = ? AND (starttime > ? OR xfgontime > ? OR timemodifiedoffline > ?)';
        $params = array($cm->instance, $from, $from, $from);
        if (!empty($insql)) {
            $select .= ' AND userid ' . $insql;
            $params = array_merge($params, $inparams);
        }

    }
    return $updates;
}

/**
 * This function receives a calendar event and returns the action associated with it, or null if there is none.
 *
 * This is used by block_myoverview in order to display the event appropriately. If null is returned then the event
 * is not displayed on the block.
 *
 * @param calendar_event $event
 * @param \core_calendar\action_factory $factory
 * @param int $userid User id to use for all capability checks, etc. Set to 0 for current user (default).
 * @return \core_calendar\local\event\entities\action_interface|null
 */
function mod_xfgon_core_calendar_provide_event_action(calendar_event $event,
                                                       \core_calendar\action_factory $factory,
                                                       int $userid = 0) {
    global $DB, $CFG, $USER;
    require_once($CFG->dirroot . '/mod/xfgon/locallib.php');

    if (!$userid) {
        $userid = $USER->id;
    }

    $cm = get_fast_modinfo($event->courseid, $userid)->instances['xfgon'][$event->instance];

    if (!$cm->uservisible) {
        // The module is not visible to the user for any reason.
        return null;
    }

    $xfgon = new xfgon($DB->get_record('xfgon', array('id' => $cm->instance), '*', MUST_EXIST));

    // Apply overrides.
    $xfgon->update_effective_access($userid);

    if (!$xfgon->is_participant($userid)) {
        // If the user is not a participant then they have
        // no action to take. This will filter out the events for teachers.
        return null;
    }

    return $factory->create_instance(
        get_string('startxfgon', 'xfgon'),
        new \moodle_url('/mod/xfgon/view.php', ['id' => $cm->id]),
        1,
        $xfgon->is_accessible()
    );
}

/**
 * Add a get_coursemodule_info function in case any xfgon type wants to add 'extra' information
 * for the course (see resource).
 *
 * Given a course_module object, this function returns any "extra" information that may be needed
 * when printing this activity in a course listing.  See get_array_of_activities() in course/lib.php.
 *
 * @param stdClass $coursemodule The coursemodule object (record).
 * @return cached_cm_info An object on information that the courses
 *                        will know about (most noticeably, an icon).
 */
function xfgon_get_coursemodule_info($coursemodule) {
    global $DB;

    $dbparams = ['id' => $coursemodule->instance];
    // $fields = 'id, name, intro, introformat, completionendreached, completiontimespent';
    $fields = 'id, name, intro, introformat';
    if (!$xfgon = $DB->get_record('xfgon', $dbparams, $fields)) {
        return false;
    }

    $result = new cached_cm_info();
    $result->name = $xfgon->name;

    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $result->content = format_module_intro('xfgon', $xfgon, $coursemodule->id, false);
    }


    return $result;
}

/**
 * Callback which returns human-readable strings describing the active completion custom rules for the module instance.
 *
 * @param cm_info|stdClass $cm object with fields ->completion and ->customdata['customcompletionrules']
 * @return array $descriptions the array of descriptions for the custom rules.
 */
function mod_xfgon_get_completion_active_rule_descriptions($cm) {
    // Values will be present in cm_info, and we assume these are up to date.
    if (empty($cm->customdata['customcompletionrules'])
        || $cm->completion != COMPLETION_TRACKING_AUTOMATIC) {
        return [];
    }

    $descriptions = [];
    foreach ($cm->customdata['customcompletionrules'] as $key => $val) {
        switch ($key) {
            case 'completionendreached':
                if (!empty($val)) {
                    $descriptions[] = get_string('completionendreached_desc', 'xfgon', $val);
                }
                break;
            case 'completiontimespent':
                if (!empty($val)) {
                    $descriptions[] = get_string('completiontimespentdesc', 'xfgon', format_time($val));
                }
                break;
            default:
                break;
        }
    }
    return $descriptions;
}

/**
 * This function calculates the minimum and maximum cutoff values for the timestart of
 * the given event.
 *
 * It will return an array with two values, the first being the minimum cutoff value and
 * the second being the maximum cutoff value. Either or both values can be null, which
 * indicates there is no minimum or maximum, respectively.
 *
 * If a cutoff is required then the function must return an array containing the cutoff
 * timestamp and error string to display to the user if the cutoff value is violated.
 *
 * A minimum and maximum cutoff return value will look like:
 * [
 *     [1505704373, 'The due date must be after the start date'],
 *     [1506741172, 'The due date must be before the cutoff date']
 * ]
 *
 * @param calendar_event $event The calendar event to get the time range for
 * @param stdClass $instance The module instance to get the range from
 * @return array
 */
function mod_xfgon_core_calendar_get_valid_event_timestart_range(\calendar_event $event, \stdClass $instance) {
    $mindate = null;
    $maxdate = null;

    if ($event->eventtype == xfgon_EVENT_TYPE_OPEN) {
        // The start time of the open event can't be equal to or after the
        // close time of the xfgon activity.
        if (!empty($instance->deadline)) {
            $maxdate = [
                $instance->deadline,
                get_string('openafterclose', 'xfgon')
            ];
        }
    } else if ($event->eventtype == xfgon_EVENT_TYPE_CLOSE) {
        // The start time of the close event can't be equal to or earlier than the
        // open time of the xfgon activity.
        if (!empty($instance->available)) {
            $mindate = [
                $instance->available,
                get_string('closebeforeopen', 'xfgon')
            ];
        }
    }

    return [$mindate, $maxdate];
}

/**
 * This function will update the xfgon module according to the
 * event that has been modified.
 *
 * It will set the available or deadline value of the xfgon instance
 * according to the type of event provided.
 *
 * @throws \moodle_exception
 * @param \calendar_event $event
 * @param stdClass $xfgon The module instance to get the range from
 */
function mod_xfgon_core_calendar_event_timestart_updated(\calendar_event $event, \stdClass $xfgon) {
    global $DB;

    if (empty($event->instance) || $event->modulename != 'xfgon') {
        return;
    }

    if ($event->instance != $xfgon->id) {
        return;
    }

    if (!in_array($event->eventtype, [xfgon_EVENT_TYPE_OPEN, xfgon_EVENT_TYPE_CLOSE])) {
        return;
    }

    $courseid = $event->courseid;
    $modulename = $event->modulename;
    $instanceid = $event->instance;
    $modified = false;

    $coursemodule = get_fast_modinfo($courseid)->instances[$modulename][$instanceid];
    $context = context_module::instance($coursemodule->id);

    // The user does not have the capability to modify this activity.
    if (!has_capability('moodle/course:manageactivities', $context)) {
        return;
    }

    if ($event->eventtype == xfgon_EVENT_TYPE_OPEN) {
        // If the event is for the xfgon activity opening then we should
        // set the start time of the xfgon activity to be the new start
        // time of the event.
        if ($xfgon->available != $event->timestart) {
            $xfgon->available = $event->timestart;
            $xfgon->timemodified = time();
            $modified = true;
        }
    } else if ($event->eventtype == xfgon_EVENT_TYPE_CLOSE) {
        // If the event is for the xfgon activity closing then we should
        // set the end time of the xfgon activity to be the new start
        // time of the event.
        if ($xfgon->deadline != $event->timestart) {
            $xfgon->deadline = $event->timestart;
            $modified = true;
        }
    }

    if ($modified) {
        $xfgon->timemodified = time();
        $DB->update_record('xfgon', $xfgon);
        $event = \core\event\course_module_updated::create_from_cm($coursemodule, $context);
        $event->trigger();
    }
}



/** Localplugin part: injection of user acquisition **/
function xfgon_extend_navigation_course() {
// function xfgon_extends_settings_navigation(){
    injector::inject();

    global $PAGE;
    global $DB;
    global $OUTPUT;
    global $COURSE;
    global $SESSION;

    $finalfinal_course_infos = get_needed_crs_infos();

    if(
        $GLOBALS['glbstg_plugenabled']==1
        and in_array($COURSE->category, $GLOBALS['glbstg_crs_category'])
        and validate_course_enrolment($COURSE->id, $DB, $GLOBALS['glbstg_crs_allowed_enrolment_types'], $GLOBALS['glbstg_crs_allow_typeswith_pass'])
        and validate_course_visibility($COURSE->id, $DB, $GLOBALS['glbstg_crs_allowhidden_courses'])
        and preg_match("/.*mod-xfgon.*/i", $PAGE->pagetype) == False
      )
    {
          //Latest Version of X5gon snippet: "load from remote x5gon server".
          $PAGE->requires->js(new moodle_url('https://platform.x5gon.org/api/v1/snippet/latest/x5gon-log.min.js'),true);

          //Send "dataobject to template"
          return $OUTPUT->render_from_template('mod_xfgon/x5gon', $finalfinal_course_infos);
    }
    // For the activity-plgn part
    else if(
        $GLOBALS['glbstg_plugenabled']==1
        and preg_match("/.*mod-xfgon-view.*/i", $PAGE->pagetype)
        and get_coursemodule_from_id('xfgon', required_param('id', PARAM_INT), 0, false, MUST_EXIST)->modname == 'xfgon'

      )
    {
          //Latest Version of X5gon snippet: "load from remote x5gon server".
          $PAGE->requires->js(new moodle_url('https://platform.x5gon.org/api/v1/snippet/latest/x5gon-log.min.js'),true);

          //Send "dataobject to template"
          return $OUTPUT->render_from_template('mod_xfgon/x5gon', $finalfinal_course_infos);
    }
    // For the activity-plgn : edit-part
    else if(
        $GLOBALS['glbstg_plugenabled']==1
        and preg_match("/.*mod-xfgon-mod.*/i", $PAGE->pagetype)
        )
    {

          //Load needed template"
          return $OUTPUT->render_from_template('mod_xfgon/x5gonedit', array());
    }
}


function get_needed_crs_infos(){

        global $PAGE;
        global $DB;
        global $OUTPUT;
        global $COURSE;
        global $SESSION;

        //Wont be used for now: will be used in production
        $course_allowedfields='id,category,fullname,shortname,summary,format,lang,timecreated,timemodified,crsurl';
        $course_allowedfieldsarray=explode(',',$course_allowedfields);
        $category_allowedfields='id,name,description,parent,coursecount,depth,path';
        $category_allowedfieldsarray=explode(',',$category_allowedfields);
        $module_allowedfields='id,course,module,instance,section,added,score,modname,modurl';
        $module_allowedfieldsarray=explode(',',$module_allowedfields);
        $moduleinstance_allowedfields='id,course,name,intro,introformat';
        $moduleinstance_allowedfieldsarray=explode(',',$moduleinstance_allowedfields);
        $context_allowedfields='id,contextlevel,instanceid,path,depth';
        $context_allowedfieldsarray=explode(',',$context_allowedfields);
        $file_allowedfields='id,contextid,component,filearea,filepath,filename,filesize,mimetype,source,author,license,timecreated,timemodified,sortorder,referencefileid,fileurl';
        $file_allowedfieldsarray=explode(',',$file_allowedfields);
        $licence_allowedfields='id,shortname,fullname,source,enabled,version';
        $licence_allowedfieldsarray=explode(',',$licence_allowedfields);

        $course_mods = get_course_mods($COURSE->id);
        $final_course_infos=new stdClass();
        $final_course_infos->course_gen_infos= $COURSE;
        $final_course_infos->course_gen_infos->crsurl =  $GLOBALS['GLBMDL_CFG']->wwwroot."/course/view.php?id=".$COURSE->id;
        $final_course_infos->course_cat_infos= $DB->get_record('course_categories', array('id' =>$COURSE->category ));


        $result = array();
        if($course_mods) {
            foreach($course_mods as $course_mod) {
                $course_mod->modurl =  $GLOBALS['GLBMDL_CFG']->wwwroot."/mod/".$course_mod->modname."/view.php?id=".$course_mod->id;
                $course_mod->course_module_instance = $DB->get_record($course_mod->modname, array('id' =>$course_mod->instance ));
                $course_mod->course_module_context = $DB->get_record('context', array('instanceid' =>$course_mod->id, 'contextlevel' => 70 ));
                $course_mod->course_module_file = $DB->get_record('files', array('contextid' =>$course_mod->course_module_context->id, 'sortorder' => 1));
                $course_mod->course_module_file_licence= null;
                if($course_mod->course_module_file){

                    $course_mod->course_module_file_licence = $DB->get_record('license', array('shortname' =>$course_mod->course_module_file->license));
                    //Addition of 'FileURl' as attribute
                    $course_mod->course_module_file->fileurl= $GLOBALS['GLBMDL_CFG']->wwwroot."/pluginfile.php/".$course_mod->course_module_context->id."/".$course_mod->course_module_file->component."/".$course_mod->course_module_file->filearea."/".$course_mod->course_module_file->itemid.$course_mod->course_module_file->filepath.$course_mod->course_module_file->filename;

                }
                $result[$course_mod->id] = $course_mod;
            }
        }
        $final_course_infos->course_modules=$result;

        //Return only the needed infos: allowed infos to the frontEnd side.
        $finalfinal_course_infos=new stdClass();
        $finalfinal_course_infos->course_gen_infos=json_encode( clean_object_o($final_course_infos->course_gen_infos, $course_allowedfieldsarray));
        $finalfinal_course_infos->course_cat_infos=json_encode( clean_object_o($final_course_infos->course_cat_infos, $category_allowedfieldsarray));
        $finalfinal_course_infos->course_modules=null;
        foreach ($final_course_infos->course_modules as $key => $value) {

              $finalcoursemodule =new stdClass();
              $finalcoursemodule->course_module_geninfos=clean_object_o($value, $module_allowedfieldsarray);
              $finalcoursemodule->course_module_instance=clean_object_o($value->course_module_instance, $moduleinstance_allowedfieldsarray);
              $finalcoursemodule->course_module_context=clean_object_o($value->course_module_context, $context_allowedfieldsarray);
              $finalcoursemodule->course_module_file=clean_object_o($value->course_module_file, $file_allowedfieldsarray);
              $finalcoursemodule->course_module_file_licence=clean_object_o($value->course_module_file_licence, $licence_allowedfieldsarray);

              $finalfinal_course_infos->course_modules["$key"]=$finalcoursemodule;
              $finalcoursemodule=null;
        }

        $finalfinal_course_infos->course_modules=  json_encode( $finalfinal_course_infos->course_modules);
        //Add 'provider token' to configs list sent to template
        $plugin_configs = get_config('mod_xfgon');
        $provider_token = 'x5gonPartnerToken';
        if( property_exists($plugin_configs,'providertoken') and $plugin_configs->providertoken!="" )  {
            $provider_token = $plugin_configs->providertoken;
        }
        $finalfinal_course_infos->provider_token=json_encode($provider_token);

        //Add 'user consent to x5gonPolicy status' data sent to template
        $finalfinal_course_infos->user_consent=json_encode(get_current_user_consentToX5gonPolicy($SESSION,$DB));


        return $finalfinal_course_infos;


}


function get_current_user_consentToX5gonPolicy($SESSION,$DB){

      //Here we assume if error: sendUserData
      $defaultUserConsent=0;
      $userConsentToBeRenderedToFront=array("userid"=>md5(1), "usertype"=>'guest',"userconsenttox5gon"=>$defaultUserConsent,"ignore"=>'0');
      try {

            //This test is an extra check for Moodle versions <3.5
            if( property_exists($SESSION,'cachestore_session') ){

                    $flatSessionInfos = call_user_func_array('array_merge', $SESSION->cachestore_session);
                    // Get all 'las_accesses' & active
                    $moodleSessionIdKeys=preg_grep("/^__lastaccess__.+$/", array_keys($flatSessionInfos));
                    $moodleSessionIdKeysTemp= $moodleSessionIdKeys;
                    // Here we take the last_active_access: we assume that the request&reloadpage are instant(the time gap is negligent to store another user_access in the db)
                    $moodleSessionIdKeysTemp= end($moodleSessionIdKeysTemp);
                    $moodleSessionIdKeysTemptemp= explode('_',$moodleSessionIdKeysTemp);
                    $moodleSessionId=end($moodleSessionIdKeysTemptemp);
                    $userSessionInfos=$DB->get_record('sessions', array('sid' => $moodleSessionId));

                    //Get all x5gon policy active version: NotArchived + X5GONLabel
                    $x5gonValidePolicyInfos=$DB->get_record('tool_policy_versions', array('name' => 'X5GON Privacy Policy', 'archived' => 0));
                    $x5gonValidePolicies=$DB->get_records('tool_policy', array('id' => $x5gonValidePolicyInfos->policyid));
                    $x5gonValidePoliciesActiveVersions=array_map(function ($value) { return  $value->currentversionid;}, $x5gonValidePolicies);

                    //Registered users in Moodle
                    if( $userSessionInfos->userid!=1 && $userSessionInfos->userid!=0 ){
                          $userConsentToBeRenderedToFront["usertype"]='internal';
                          $userConsentToBeRenderedToFront["userid"]=md5($userSessionInfos->userid);
                          $userPoliciesAcceptances=$DB->get_records('tool_policy_acceptances', array('userid' => $userSessionInfos->userid) );
                          foreach ($userPoliciesAcceptances as $key => $acceptance) {
                                //Compare policyversionAgreed with active/notdraft/Notdisactivated x5gonPolicyVersions
                                if(  in_array($acceptance->policyversionid, $x5gonValidePoliciesActiveVersions) ){
                                    $userConsentToBeRenderedToFront["userconsenttox5gon"]=$acceptance->status;
                                }
                          }
                    }else{
                        //Anonymous Moodle users: 0(anonymous users not entreing as guest)/1(anonymous users entreing as guest)
                        //Default '$userConsentToBeRenderedToFront' will be sent
                        //return only the sessionid of these type of users (anonymous & guets)
                        $userConsentToBeRenderedToFront["userid"]=md5($userSessionInfos->sid);
                    }
             }

      } catch (\Exception $e) {

            $moodle_version= get_config('moodle', 'version');
            //'Moodle versionnumver of 3.5 ==2018051700'
            if( $moodle_version<2018051700 )
            {
                  $userConsentToBeRenderedToFront["ignore"]='1';
            }

      }

      return $userConsentToBeRenderedToFront;
}


function clean_object_o($orig_object, $desired_propreties){

    $treated_object=null;
    if($orig_object){
        $treated_object =new stdClass();
        foreach ($orig_object as $key => $value)
        {

            if( in_array($key, $desired_propreties) ){

                $treated_object->$key = addslashes($value);
            }
        }
    }
    return $treated_object;
}

function send_post_request($url, $data) {
    global $CFG;

    $ch = curl_init($url);
    $datastring = json_encode($data);
    // Use moodle proxy settings if existent
    if (!empty($CFG->proxyhost)) {
        $proxyurl = $CFG->proxyhost;
        if (!empty($CFG->proxyport)) {
            $proxyurl = $proxyurl. ":" .$CFG->proxyport;
        }
        curl_setopt($ch, CURLOPT_PROXY, $proxyurl);

        if (!empty($CFG->proxytype)) {
            // Only set CURLOPT_PROXYTYPE if it's something other than the curl-default http
            if ($CFG->proxytype == 'SOCKS5') {
                curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
            }
        }
        if (!empty($CFG->proxyuser) and !empty($CFG->proxypassword)) {
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $CFG->proxyuser.':'.$CFG->proxypassword);
        }

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $datastring);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Content-Type: application/json',
      'Content-Length: ' . strlen($datastring))
    );
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    if ($error !== '') {
        throw new \Exception($error);
    }
    return json_decode($response);
}

function send_get_request($url) {
    global $CFG;

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    // Use moodle proxy settings if existent
    if (!empty($CFG->proxyhost)) {
        $proxyurl = $CFG->proxyhost;
        if (!empty($CFG->proxyport)) {
            $proxyurl = $proxyurl. ":" .$CFG->proxyport;
        }
        curl_setopt($ch, CURLOPT_PROXY, $proxyurl);

        if (!empty($CFG->proxytype)) {
            // Only set CURLOPT_PROXYTYPE if it's something other than the curl-default http
            if ($CFG->proxytype == 'SOCKS5') {
                curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
            }
        }
        if (!empty($CFG->proxyuser) and !empty($CFG->proxypassword)) {
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $CFG->proxyuser.':'.$CFG->proxypassword);
        }

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    }
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    if ($error !== '') {
        throw new \Exception($error);
    }
    return json_decode($response);
}
