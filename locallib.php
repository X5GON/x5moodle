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

/** Make sure this isn't being directly accessed */
defined('MOODLE_INTERNAL') || die();

/** Include the files that are required by this module */
require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/xfgon/lib.php');
require_once($CFG->libdir . '/filelib.php');

/** This page */
define('xfgon_THISPAGE', 0);
/** Next page -> any page not seen before */
define("xfgon_UNSEENPAGE", 1);
/** Next page -> any page not answered correctly */
define("xfgon_UNANSWEREDPAGE", 2);
/** Jump to Next Page */
define("xfgon_NEXTPAGE", -1);
/** End of Lesson */
define("xfgon_EOL", -9);
/** Jump to an unseen page within a branch and end of branch or end of xfgon */
define("xfgon_UNSEENBRANCHPAGE", -50);
/** Jump to Previous Page */
define("xfgon_PREVIOUSPAGE", -40);
/** Jump to a random page within a branch and end of branch or end of xfgon */
define("xfgon_RANDOMPAGE", -60);
/** Jump to a random Branch */
define("xfgon_RANDOMBRANCH", -70);
/** Cluster Jump */
define("xfgon_CLUSTERJUMP", -80);
/** Undefined */
define("xfgon_UNDEFINED", -99);

/** xfgon_MAX_EVENT_LENGTH = 432000 ; 5 days maximum */
define("xfgon_MAX_EVENT_LENGTH", "432000");

/** Answer format is HTML */
define("xfgon_ANSWER_HTML", "HTML");

//////////////////////////////////////////////////////////////////////////////////////
/// Any other xfgon functions go here.  Each of them must have a name that
/// starts with xfgon_

/**
 * Calculates a user's grade for a xfgon.
 *
 * @param object $xfgon The xfgon that the user is taking.
 * @param int $retries The attempt number.
 * @param int $userid Id of the user (optional, default current user).
 * @return object { nquestions => number of questions answered
                    attempts => number of question attempts
                    total => max points possible
                    earned => points earned by student
                    grade => calculated percentage grade
                    nmanual => number of manually graded questions
                    manualpoints => point value for manually graded questions }
 */
function xfgon_grade($xfgon, $ntries, $userid = 0) {
    global $USER, $DB;

    if (empty($userid)) {
        $userid = $USER->id;
    }

    // Zero out everything
    $ncorrect     = 0;
    $nviewed      = 0;
    $score        = 0;
    $nmanual      = 0;
    $manualpoints = 0;
    $thegrade     = 0;
    $nquestions   = 0;
    $total        = 0;
    $earned       = 0;

    $params = array ("xfgonid" => $xfgon->id, "userid" => $userid, "retry" => $ntries);

    if ($total) { // not zero
        $thegrade = round(100 * $earned / $total, 5);
    }

    // Build the grade information object
    $gradeinfo               = new stdClass;
    $gradeinfo->nquestions   = $nquestions;
    $gradeinfo->attempts     = $nviewed;
    $gradeinfo->total        = $total;
    $gradeinfo->earned       = $earned;
    $gradeinfo->grade        = $thegrade;
    $gradeinfo->nmanual      = $nmanual;
    $gradeinfo->manualpoints = $manualpoints;

    return $gradeinfo;
}

/**
 * Determines if a user can view the left menu.  The determining factor
 * is whether a user has a grade greater than or equal to the xfgon setting
 * of displayleftif
 *
 * @param object $xfgon Lesson object of the current xfgon
 * @return boolean 0 if the user cannot see, or $xfgon->displayleft to keep displayleft unchanged
 **/
function xfgon_displayleftif($xfgon) {
    global $CFG, $USER, $DB;

    if (!empty($xfgon->displayleftif)) {
        // get the current user's max grade for this xfgon
        $params = array ("userid" => $USER->id, "xfgonid" => $xfgon->id);

    }

    // if we get to here, keep the original state of displayleft xfgon setting
    return $xfgon->displayleft;
}

/**
 *
 * @param $cm
 * @param $xfgon
 * @param $page
 * @return unknown_type
 */
function xfgon_add_fake_blocks($page, $cm, $xfgon, $timer = null) {
    $bc = xfgon_menu_block_contents($cm->id, $xfgon);
    if (!empty($bc)) {
        $regions = $page->blocks->get_regions();
        $firstregion = reset($regions);
        $page->blocks->add_fake_block($bc, $firstregion);
    }

    $bc = xfgon_mediafile_block_contents($cm->id, $xfgon);
    if (!empty($bc)) {
        $page->blocks->add_fake_block($bc, $page->blocks->get_default_region());
    }

    if (!empty($timer)) {
        $bc = xfgon_clock_block_contents($cm->id, $xfgon, $timer, $page);
        if (!empty($bc)) {
            $page->blocks->add_fake_block($bc, $page->blocks->get_default_region());
        }
    }
}

/**
 * If there is a media file associated with this
 * xfgon, return a block_contents that displays it.
 *
 * @param int $cmid Course Module ID for this xfgon
 * @param object $xfgon Full xfgon record object
 * @return block_contents
 **/
function xfgon_mediafile_block_contents($cmid, $xfgon) {
    global $OUTPUT;
    if (empty($xfgon->mediafile)) {
        return null;
    }

    $options = array();
    $options['menubar'] = 0;
    $options['location'] = 0;
    $options['left'] = 5;
    $options['top'] = 5;
    $options['scrollbars'] = 1;
    $options['resizable'] = 1;
    $options['width'] = $xfgon->mediawidth;
    $options['height'] = $xfgon->mediaheight;

    $link = new moodle_url('/mod/xfgon/mediafile.php?id='.$cmid);
    $action = new popup_action('click', $link, 'xfgonmediafile', $options);
    $content = $OUTPUT->action_link($link, get_string('mediafilepopup', 'xfgon'), $action, array('title'=>get_string('mediafilepopup', 'xfgon')));

    $bc = new block_contents();
    $bc->title = get_string('linkedmedia', 'xfgon');
    $bc->attributes['class'] = 'mediafile block';
    $bc->content = $content;

    return $bc;
}

/**
 * If a timed xfgon and not a teacher, then
 * return a block_contents containing the clock.
 *
 * @param int $cmid Course Module ID for this xfgon
 * @param object $xfgon Full xfgon record object
 * @param object $timer Full timer record object
 * @return block_contents
 **/
function xfgon_clock_block_contents($cmid, $xfgon, $timer, $page) {
    // Display for timed xfgons and for students only
    $context = context_module::instance($cmid);
    if ($xfgon->timelimit == 0 || has_capability('mod/xfgon:manage', $context)) {
        return null;
    }

    $content = '<div id="xfgon-timer">';
    $content .=  $xfgon->time_remaining($timer->starttime);
    $content .= '</div>';

    $clocksettings = array('starttime' => $timer->starttime, 'servertime' => time(), 'testlength' => $xfgon->timelimit);
    $page->requires->data_for_js('clocksettings', $clocksettings, true);
    $page->requires->strings_for_js(array('timeisup'), 'xfgon');
    $page->requires->js('/mod/xfgon/timer.js');
    $page->requires->js_init_call('show_clock');

    $bc = new block_contents();
    $bc->title = get_string('timeremaining', 'xfgon');
    $bc->attributes['class'] = 'clock block';
    $bc->content = $content;

    return $bc;
}

/**
 * If left menu is turned on, then this will
 * print the menu in a block
 *
 * @param int $cmid Course Module ID for this xfgon
 * @param xfgon $xfgon Full xfgon record object
 * @return void
 **/
function xfgon_menu_block_contents($cmid, $xfgon) {
    global $CFG, $DB;

    if (!$xfgon->displayleft) {
        return null;
    }

    $pages = $xfgon->load_all_pages();
    foreach ($pages as $page) {
        if ((int)$page->prevpageid === 0) {
            $pageid = $page->id;
            break;
        }
    }
    $currentpageid = optional_param('pageid', $pageid, PARAM_INT);

    if (!$pageid || !$pages) {
        return null;
    }

    $content = '<a href="#maincontent" class="accesshide">' .
        get_string('skip', 'xfgon') .
        "</a>\n<div class=\"menuwrapper\">\n<ul>\n";

    while ($pageid != 0) {
        $page = $pages[$pageid];

        // Only process branch tables with display turned on
        if ($page->displayinmenublock && $page->display) {
            if ($page->id == $currentpageid) {
                $content .= '<li class="selected">'.format_string($page->title,true)."</li>\n";
            } else {
                $content .= "<li class=\"notselected\"><a href=\"$CFG->wwwroot/mod/xfgon/view.php?id=$cmid&amp;pageid=$page->id\">".format_string($page->title,true)."</a></li>\n";
            }

        }
        $pageid = $page->nextpageid;
    }
    $content .= "</ul>\n</div>\n";

    $bc = new block_contents();
    $bc->title = get_string('xfgonmenu', 'xfgon');
    $bc->attributes['class'] = 'menu block';
    $bc->content = $content;

    return $bc;
}

/**
 * Adds header buttons to the page for the xfgon
 *
 * @param object $cm
 * @param object $context
 * @param bool $extraeditbuttons
 * @param int $xfgonpageid
 */
function xfgon_add_header_buttons($cm, $context, $extraeditbuttons=false, $xfgonpageid=null) {
    global $CFG, $PAGE, $OUTPUT;
    if (has_capability('mod/xfgon:edit', $context) && $extraeditbuttons) {
        if ($xfgonpageid === null) {
            print_error('invalidpageid', 'xfgon');
        }
        if (!empty($xfgonpageid) && $xfgonpageid != xfgon_EOL) {
            $url = new moodle_url('/mod/xfgon/editpage.php', array(
                'id'       => $cm->id,
                'pageid'   => $xfgonpageid,
                'edit'     => 1,
                'returnto' => $PAGE->url->out(false)
            ));
            $PAGE->set_button($OUTPUT->single_button($url, get_string('editpagecontent', 'xfgon')));
        }
    }
}

/**
 * This is a function used to detect media types and generate html code.
 *
 * @global object $CFG
 * @global object $PAGE
 * @param object $xfgon
 * @param object $context
 * @return string $code the html code of media
 */
function xfgon_get_media_html($xfgon, $context) {
    global $CFG, $PAGE, $OUTPUT;
    require_once("$CFG->libdir/resourcelib.php");

    // get the media file link
    if (strpos($xfgon->mediafile, '://') !== false) {
        $url = new moodle_url($xfgon->mediafile);
    } else {
        // the timemodified is used to prevent caching problems, instead of '/' we should better read from files table and use sortorder
        $url = moodle_url::make_pluginfile_url($context->id, 'mod_xfgon', 'mediafile', $xfgon->timemodified, '/', ltrim($xfgon->mediafile, '/'));
    }
    $title = $xfgon->mediafile;

    $clicktoopen = html_writer::link($url, get_string('download'));

    $mimetype = resourcelib_guess_url_mimetype($url);

    $extension = resourcelib_get_extension($url->out(false));

    $mediamanager = core_media_manager::instance($PAGE);
    $embedoptions = array(
        core_media_manager::OPTION_TRUSTED => true,
        core_media_manager::OPTION_BLOCK => true
    );

    // find the correct type and print it out
    if (in_array($mimetype, array('image/gif','image/jpeg','image/png'))) {  // It's an image
        $code = resourcelib_embed_image($url, $title);

    } else if ($mediamanager->can_embed_url($url, $embedoptions)) {
        // Media (audio/video) file.
        $code = $mediamanager->embed_url($url, $title, 0, 0, $embedoptions);

    } else {
        // anything else - just try object tag enlarged as much as possible
        $code = resourcelib_embed_general($url, $title, $clicktoopen, $mimetype);
    }

    return $code;
}

/**
 * Logic to happen when a/some group(s) has/have been deleted in a course.
 *
 * @param int $courseid The course ID.
 * @param int $groupid The group id if it is known
 * @return void
 */
function xfgon_process_group_deleted_in_course($courseid, $groupid = null) {
    global $DB;

    $params = array('courseid' => $courseid);

}


/**
 * Return user's deadline for all xfgons in a course, hereby taking into account group and user overrides.
 *
 * @param int $courseid the course id.
 * @return object An object with of all xfgonsids and close unixdates in this course,
 * taking into account the most lenient overrides, if existing and 0 if no close date is set.
 */
function xfgon_get_user_deadline($courseid) {
    global $DB, $USER;

    // For teacher and manager/admins return xfgon's deadline.
    if (has_capability('moodle/course:update', context_course::instance($courseid))) {
        $sql = "SELECT xfgon.id, xfgon.deadline AS userdeadline
                  FROM {xfgon} xfgon
                 WHERE xfgon.course = :courseid";

        $results = $DB->get_records_sql($sql, array('courseid' => $courseid));
        return $results;
    }

    $sql = "SELECT a.id,
                   COALESCE(v.userclose, v.groupclose, a.deadline, 0) AS userdeadline
              FROM (
                      SELECT xfgon.id as xfgonid,
                             MAX(leo.deadline) AS userclose, MAX(qgo.deadline) AS groupclose
                        FROM {xfgon} xfgon
                   -- LEFT JOIN {xfgon_overrides} leo on xfgon.id = leo.xfgonid AND leo.userid = :userid
                   -- LEFT JOIN {groups_members} gm ON gm.userid = :useringroupid
                   -- LEFT JOIN {xfgon_overrides} qgo on xfgon.id = qgo.xfgonid AND qgo.groupid = gm.groupid
                       WHERE xfgon.course = :courseid
                    GROUP BY xfgon.id
                   ) v
              JOIN {xfgon} a ON a.id = v.xfgonid";

    $results = $DB->get_records_sql($sql, array('userid' => $USER->id, 'useringroupid' => $USER->id, 'courseid' => $courseid));
    return $results;

}

/**
 * Abstract class that page type's MUST inherit from.
 *
 * This is the abstract class that ALL add page type forms must extend.
 * You will notice that all but two of the methods this class contains are final.
 * Essentially the only thing that extending classes can do is extend custom_definition.
 * OR if it has a special requirement on creation it can extend construction_override
 *
 * @abstract
 * @copyright  2009 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class xfgon_add_page_form_base extends moodleform {

    /**
     * This is the classic define that is used to identify this pagetype.
     * Will be one of xfgon_*
     * @var int
     */
    public $qtype;

    /**
     * The simple string that describes the page type e.g. truefalse, multichoice
     * @var string
     */
    public $qtypestring;

    /**
     * An array of options used in the htmleditor
     * @var array
     */
    protected $editoroptions = array();

    /**
     * True if this is a standard page of false if it does something special.
     * Questions are standard pages, branch tables are not
     * @var bool
     */
    protected $standard = true;

    /**
     * Answer format supported by question type.
     */
    protected $answerformat = '';

    /**
     * Response format supported by question type.
     */
    protected $responseformat = '';

    /**
     * Each page type can and should override this to add any custom elements to
     * the basic form that they want
     */
    public function custom_definition() {}

    /**
     * Returns answer format used by question type.
     */
    public function get_answer_format() {
        return $this->answerformat;
    }

    /**
     * Returns response format used by question type.
     */
    public function get_response_format() {
        return $this->responseformat;
    }

    /**
     * Used to determine if this is a standard page or a special page
     * @return bool
     */
    public final function is_standard() {
        return (bool)$this->standard;
    }

    /**
     * Add the required basic elements to the form.
     *
     * This method adds the basic elements to the form including title and contents
     * and then calls custom_definition();
     */
    public final function definition() {
        $mform = $this->_form;
        $editoroptions = $this->_customdata['editoroptions'];

        if ($this->qtypestring != 'selectaqtype') {
            if ($this->_customdata['edit']) {
                $mform->addElement('header', 'qtypeheading', get_string('edit'. $this->qtypestring, 'xfgon'));
            } else {
                $mform->addElement('header', 'qtypeheading', get_string('add'. $this->qtypestring, 'xfgon'));
            }
        }

        if (!empty($this->_customdata['returnto'])) {
            $mform->addElement('hidden', 'returnto', $this->_customdata['returnto']);
            $mform->setType('returnto', PARAM_URL);
        }

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'pageid');
        $mform->setType('pageid', PARAM_INT);

        if ($this->standard === true) {
            $mform->addElement('hidden', 'qtype');
            $mform->setType('qtype', PARAM_INT);

            $mform->addElement('text', 'title', get_string('pagetitle', 'xfgon'), array('size'=>70));
            $mform->setType('title', PARAM_TEXT);
            $mform->addRule('title', get_string('required'), 'required', null, 'client');

            $this->editoroptions = array('noclean'=>true, 'maxfiles'=>EDITOR_UNLIMITED_FILES, 'maxbytes'=>$this->_customdata['maxbytes']);
            $mform->addElement('editor', 'contents_editor', get_string('pagecontents', 'xfgon'), null, $this->editoroptions);
            $mform->setType('contents_editor', PARAM_RAW);
            $mform->addRule('contents_editor', get_string('required'), 'required', null, 'client');
        }

        $this->custom_definition();

        if ($this->_customdata['edit'] === true) {
            $mform->addElement('hidden', 'edit', 1);
            $mform->setType('edit', PARAM_BOOL);
            $this->add_action_buttons(get_string('cancel'), get_string('savepage', 'xfgon'));
        } else if ($this->qtype === 'questiontype') {
            $this->add_action_buttons(get_string('cancel'), get_string('addaquestionpage', 'xfgon'));
        } else {
            $this->add_action_buttons(get_string('cancel'), get_string('savepage', 'xfgon'));
        }
    }

    /**
     * Convenience function: Adds a jumpto select element
     *
     * @param string $name
     * @param string|null $label
     * @param int $selected The page to select by default
     */
    protected final function add_jumpto($name, $label=null, $selected=xfgon_NEXTPAGE) {
        $title = get_string("jump", "xfgon");
        if ($label === null) {
            $label = $title;
        }
        if (is_int($name)) {
            $name = "jumpto[$name]";
        }
        $this->_form->addElement('select', $name, $label, $this->_customdata['jumpto']);
        $this->_form->setDefault($name, $selected);
        $this->_form->addHelpButton($name, 'jumps', 'xfgon');
    }

    /**
     * Convenience function: Adds a score input element
     *
     * @param string $name
     * @param string|null $label
     * @param mixed $value The default value
     */
    protected final function add_score($name, $label=null, $value=null) {
        if ($label === null) {
            $label = get_string("score", "xfgon");
        }

        if (is_int($name)) {
            $name = "score[$name]";
        }
        $this->_form->addElement('text', $name, $label, array('size'=>5));
        $this->_form->setType($name, PARAM_INT);
        if ($value !== null) {
            $this->_form->setDefault($name, $value);
        }
        $this->_form->addHelpButton($name, 'score', 'xfgon');

        // Score is only used for custom scoring. Disable the element when not in use to stop some confusion.
        if (!$this->_customdata['xfgon']->custom) {
            $this->_form->freeze($name);
        }
    }

    /**
     * Convenience function: Adds an answer editor
     *
     * @param int $count The count of the element to add
     * @param string $label, null means default
     * @param bool $required
     * @param string $format
     * @return void
     */
    protected final function add_answer($count, $label = null, $required = false, $format= '') {
        if ($label === null) {
            $label = get_string('answer', 'xfgon');
        }

        if ($format == xfgon_ANSWER_HTML) {
            $this->_form->addElement('editor', 'answer_editor['.$count.']', $label,
                    array('rows' => '4', 'columns' => '80'),
                    array('noclean' => true, 'maxfiles' => EDITOR_UNLIMITED_FILES, 'maxbytes' => $this->_customdata['maxbytes']));
            $this->_form->setType('answer_editor['.$count.']', PARAM_RAW);
            $this->_form->setDefault('answer_editor['.$count.']', array('text' => '', 'format' => FORMAT_HTML));
        } else {
            $this->_form->addElement('text', 'answer_editor['.$count.']', $label,
                    array('size' => '50', 'maxlength' => '200'));
            $this->_form->setType('answer_editor['.$count.']', PARAM_TEXT);
        }

        if ($required) {
            $this->_form->addRule('answer_editor['.$count.']', get_string('required'), 'required', null, 'client');
        }
    }
    /**
     * Convenience function: Adds an response editor
     *
     * @param int $count The count of the element to add
     * @param string $label, null means default
     * @param bool $required
     * @return void
     */
    protected final function add_response($count, $label = null, $required = false) {
        if ($label === null) {
            $label = get_string('response', 'xfgon');
        }
        $this->_form->addElement('editor', 'response_editor['.$count.']', $label,
                 array('rows' => '4', 'columns' => '80'),
                 array('noclean' => true, 'maxfiles' => EDITOR_UNLIMITED_FILES, 'maxbytes' => $this->_customdata['maxbytes']));
        $this->_form->setType('response_editor['.$count.']', PARAM_RAW);
        $this->_form->setDefault('response_editor['.$count.']', array('text' => '', 'format' => FORMAT_HTML));

        if ($required) {
            $this->_form->addRule('response_editor['.$count.']', get_string('required'), 'required', null, 'client');
        }
    }

    /**
     * A function that gets called upon init of this object by the calling script.
     *
     * This can be used to process an immediate action if required. Currently it
     * is only used in special cases by non-standard page types.
     *
     * @return bool
     */
    public function construction_override($pageid, xfgon $xfgon) {
        return true;
    }
}



/**
 * Class representation of a xfgon
 *
 * This class is used the interact with, and manage a xfgon once instantiated.
 * If you need to fetch a xfgon object you can do so by calling
 *
 * <code>
 * xfgon::load($xfgonid);
 * // or
 * $xfgonrecord = $DB->get_record('xfgon', $xfgonid);
 * $xfgon = new xfgon($xfgonrecord);
 * </code>
 *
 * The class itself extends xfgon_base as all classes within the xfgon module should
 *
 * These properties are from the database
 * @property int $id The id of this xfgon
 * @property int $course The ID of the course this xfgon belongs to
 * @property string $name The name of this xfgon
 * @property int $practice Flag to toggle this as a practice xfgon
 * @property int $modattempts Toggle to allow the user to go back and review answers
 * @property int $usepassword Toggle the use of a password for entry
 * @property string $password The password to require users to enter
 * @property int $dependency ID of another xfgon this xfgon is dependent on
 * @property string $conditions Conditions of the xfgon dependency
 * @property int $grade The maximum grade a user can achieve (%)
 * @property int $custom Toggle custom scoring on or off
 * @property int $ongoing Toggle display of an ongoing score
 * @property int $usemaxgrade How retakes are handled (max=1, mean=0)
 * @property int $maxanswers The max number of answers or branches
 * @property int $maxattempts The maximum number of attempts a user can record
 * @property int $review Toggle use or wrong answer review button
 * @property int $nextpagedefault Override the default next page
 * @property int $feedback Toggles display of default feedback
 * @property int $minquestions Sets a minimum value of pages seen when calculating grades
 * @property int $maxpages Maximum number of pages this xfgon can contain
 * @property int $retake Flag to allow users to retake a xfgon
 * @property int $activitylink Relate this xfgon to another xfgon
 * @property string $mediafile File to pop up to or webpage to display
 * @property int $mediaheight Sets the height of the media file popup
 * @property int $mediawidth Sets the width of the media file popup
 * @property int $mediaclose Toggle display of a media close button
 * @property int $slideshow Flag for whether branch pages should be shown as slideshows
 * @property int $width Width of slideshow
 * @property int $height Height of slideshow
 * @property string $bgcolor Background colour of slideshow
 * @property int $displayleft Display a left menu
 * @property int $displayleftif Sets the condition on which the left menu is displayed
 * @property int $progressbar Flag to toggle display of a xfgon progress bar
 * @property int $available Timestamp of when this xfgon becomes available
 * @property int $deadline Timestamp of when this xfgon is no longer available
 * @property int $timemodified Timestamp when xfgon was last modified
 * @property int $allowofflineattempts Whether to allow the xfgon to be attempted offline in the mobile app
 *
 * These properties are calculated
 * @property int $firstpageid Id of the first page of this xfgon (prevpageid=0)
 * @property int $lastpageid Id of the last page of this xfgon (nextpageid=0)
 *
 * @copyright  2009 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class xfgon extends xfgon_base {

    /**
     * The id of the first page (where prevpageid = 0) gets set and retrieved by
     * {@see get_firstpageid()} by directly calling <code>$xfgon->firstpageid;</code>
     * @var int
     */
    protected $firstpageid = null;
    /**
     * The id of the last page (where nextpageid = 0) gets set and retrieved by
     * {@see get_lastpageid()} by directly calling <code>$xfgon->lastpageid;</code>
     * @var int
     */
    protected $lastpageid = null;
    /**
     * An array used to cache the pages associated with this xfgon after the first
     * time they have been loaded.
     * A note to developers: If you are going to be working with MORE than one or
     * two pages from a xfgon you should probably call {@see $xfgon->load_all_pages()}
     * in order to save excess database queries.
     * @var array An array of xfgon_page objects
     */
    protected $pages = array();
    /**
     * Flag that gets set to true once all of the pages associated with the xfgon
     * have been loaded.
     * @var bool
     */
    protected $loadedallpages = false;

    /**
     * Course module object gets set and retrieved by directly calling <code>$xfgon->cm;</code>
     * @see get_cm()
     * @var stdClass
     */
    protected $cm = null;

    /**
     * Course object gets set and retrieved by directly calling <code>$xfgon->courserecord;</code>
     * @see get_courserecord()
     * @var stdClass
     */
    protected $courserecord = null;

    /**
     * Context object gets set and retrieved by directly calling <code>$xfgon->context;</code>
     * @see get_context()
     * @var stdClass
     */
    protected $context = null;

    /**
     * Constructor method
     *
     * @param object $properties
     * @param stdClass $cm course module object
     * @param stdClass $course course object
     * @since Moodle 3.3
     */
    public function __construct($properties, $cm = null, $course = null) {
        parent::__construct($properties);
        $this->cm = $cm;
        $this->courserecord = $course;
    }

    /**
     * Simply generates a xfgon object given an array/object of properties
     * Overrides {@see xfgon_base->create()}
     * @static
     * @param object|array $properties
     * @return xfgon
     */
    public static function create($properties) {
        return new xfgon($properties);
    }

    /**
     * Generates a xfgon object from the database given its id
     * @static
     * @param int $xfgonid
     * @return xfgon
     */
    public static function load($xfgonid) {
        global $DB;

        if (!$xfgon = $DB->get_record('xfgon', array('id' => $xfgonid))) {
            print_error('invalidcoursemodule');
        }
        return new xfgon($xfgon);
    }

    /**
     * Deletes this xfgon from the database
     */
    public function delete() {
        global $CFG, $DB;
        require_once($CFG->libdir.'/gradelib.php');
        require_once($CFG->dirroot.'/calendar/lib.php');

        $cm = get_coursemodule_from_instance('xfgon', $this->properties->id, $this->properties->course);
        $context = context_module::instance($cm->id);

        $this->delete_all_overrides();

        grade_update('mod/xfgon', $this->properties->course, 'mod', 'xfgon', $this->properties->id, 0, null, array('deleted'=>1));

        // We must delete the module record after we delete the grade item.
        $DB->delete_records("xfgon", array("id"=>$this->properties->id));
        if ($events = $DB->get_records('event', array("modulename"=>'xfgon', "instance"=>$this->properties->id))) {
            $coursecontext = context_course::instance($cm->course);
            foreach($events as $event) {
                $event->context = $coursecontext;
                $event = calendar_event::load($event);
                $event->delete();
            }
        }

        // Delete files associated with this module.
        $fs = get_file_storage();
        $fs->delete_area_files($context->id);

        return true;
    }

    /**
     * Deletes a xfgon override from the database and clears any corresponding calendar events
     *
     * @param int $overrideid The id of the override being deleted
     * @return bool true on success
     */
    public function delete_override($overrideid) {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/calendar/lib.php');

        $cm = get_coursemodule_from_instance('xfgon', $this->properties->id, $this->properties->course);

        // Delete the events.
        $conds = array('modulename' => 'xfgon',
                'instance' => $this->properties->id);

        $events = $DB->get_records('event', $conds);
        foreach ($events as $event) {
            $eventold = calendar_event::load($event);
            $eventold->delete();
        }

        // Set the common parameters for one of the events we will be triggering.
        $params = array(
            'objectid' => $override->id,
            'context' => context_module::instance($cm->id),
            'other' => array(
                'xfgonid' => $override->xfgonid
            )
        );

        // Trigger the override deleted event.
        $event->trigger();

        return true;
    }

    /**
     * Deletes all xfgon overrides from the database and clears any corresponding calendar events
     */
    public function delete_all_overrides() {
        global $DB;

    }

    /**
     * Checks user enrollment in the current course.
     *
     * @param int $userid
     * @return null|stdClass user record
     */
    public function is_participant($userid) {
        return is_enrolled($this->get_context(), $userid, 'mod/xfgon:view', $this->show_only_active_users());
    }

    /**
     * Check is only active users in course should be shown.
     *
     * @return bool true if only active users should be shown.
     */
    public function show_only_active_users() {
        return !has_capability('moodle/course:viewsuspendedusers', $this->get_context());
    }

    /**
     * Updates the xfgon properties with override information for a user.
     *
     * Algorithm:  For each xfgon setting, if there is a matching user-specific override,
     *   then use that otherwise, if there are group-specific overrides, return the most
     *   lenient combination of them.  If neither applies, leave the quiz setting unchanged.
     *
     *   Special case: if there is more than one password that applies to the user, then
     *   xfgon->extrapasswords will contain an array of strings giving the remaining
     *   passwords.
     *
     * @param int $userid The userid.
     */
    public function update_effective_access($userid) {
        global $DB;
        // To be improved...
    }

    /**
     * Fetches messages from the session that may have been set in previous page
     * actions.
     *
     * <code>
     * // Do not call this method directly instead use
     * $xfgon->messages;
     * </code>
     *
     * @return array
     */
    protected function get_messages() {
        global $SESSION;

        $messages = array();
        if (!empty($SESSION->xfgon_messages) && is_array($SESSION->xfgon_messages) && array_key_exists($this->properties->id, $SESSION->xfgon_messages)) {
            $messages = $SESSION->xfgon_messages[$this->properties->id];
            unset($SESSION->xfgon_messages[$this->properties->id]);
        }

        return $messages;
    }

    /**
     * Get all of the attempts for the current user.
     *
     * @param int $retries
     * @param bool $correct Optional: only fetch correct attempts
     * @param int $pageid Optional: only fetch attempts at the given page
     * @param int $userid Optional: defaults to the current user if not set
     * @return array|false
     */
    public function get_attempts($retries, $correct=false, $pageid=null, $userid=null) {
        global $USER, $DB;
        // To be improved...
    }


    /**
     * Get a list of content pages (formerly known as branch tables) viewed in the xfgon for the given user during an attempt.
     *
     * @param  int $xfgonattempt the xfgon attempt number (also known as retries)
     * @param  int $userid        the user id to retrieve the data from
     * @param  string $sort          an order to sort the results in (a valid SQL ORDER BY parameter)
     * @param  string $fields        a comma separated list of fields to return
     * @return array of pages
     * @since  Moodle 3.3
     */
    public function get_content_pages_viewed($xfgonattempt, $userid = null, $sort = '', $fields = '*') {
        global $USER, $DB;
        // To be improved...
    }

    /**
     * Returns the first page for the xfgon or false if there isn't one.
     *
     * This method should be called via the magic method __get();
     * <code>
     * $firstpage = $xfgon->firstpage;
     * </code>
     *
     * @return xfgon_page|bool Returns the xfgon_page specialised object or false
     */
    protected function get_firstpage() {
        $pages = $this->load_all_pages();
        if (count($pages) > 0) {
            foreach ($pages as $page) {
                if ((int)$page->prevpageid === 0) {
                    return $page;
                }
            }
        }
        return false;
    }

    /**
     * Returns the last page for the xfgon or false if there isn't one.
     *
     * This method should be called via the magic method __get();
     * <code>
     * $lastpage = $xfgon->lastpage;
     * </code>
     *
     * @return xfgon_page|bool Returns the xfgon_page specialised object or false
     */
    protected function get_lastpage() {
        $pages = $this->load_all_pages();
        if (count($pages) > 0) {
            foreach ($pages as $page) {
                if ((int)$page->nextpageid === 0) {
                    return $page;
                }
            }
        }
        return false;
    }

    /**
     * Returns the id of the first page of this xfgon. (prevpageid = 0)
     * @return int
     */
    protected function get_firstpageid() {
        global $DB;
        return $this->firstpageid;
    }

    /**
     * Returns the id of the last page of this xfgon. (nextpageid = 0)
     * @return int
     */
    public function get_lastpageid() {
        global $DB;
        return $this->lastpageid;
    }

     /**
     * Gets the next page id to display after the one that is provided.
     * @param int $nextpageid
     * @return bool
     */
    public function get_next_page($nextpageid) {
        global $USER, $DB;
        $allpages = $this->load_all_pages();

        return xfgon_EOL;
    }

    /**
     * Sets a message against the session for this xfgon that will displayed next
     * time the xfgon processes messages
     *
     * @param string $message
     * @param string $class
     * @param string $align
     * @return bool
     */
    public function add_message($message, $class="notifyproblem", $align='center') {
        global $SESSION;

        if (empty($SESSION->xfgon_messages) || !is_array($SESSION->xfgon_messages)) {
            $SESSION->xfgon_messages = array();
            $SESSION->xfgon_messages[$this->properties->id] = array();
        } else if (!array_key_exists($this->properties->id, $SESSION->xfgon_messages)) {
            $SESSION->xfgon_messages[$this->properties->id] = array();
        }

        $SESSION->xfgon_messages[$this->properties->id][] = array($message, $class, $align);

        return true;
    }

    /**
     * Check if the xfgon is accessible at the present time
     * @return bool True if the xfgon is accessible, false otherwise
     */
    public function is_accessible() {
        $available = $this->properties->available;
        $deadline = $this->properties->deadline;
        return (($available == 0 || time() >= $available) && ($deadline == 0 || time() < $deadline));
    }

    /**
     * Starts the xfgon time for the current user
     * @return bool Returns true
     */
    public function start_timer() {
        global $USER, $DB;

        $cm = get_coursemodule_from_instance('xfgon', $this->properties()->id, $this->properties()->course,
            false, MUST_EXIST);

        // Trigger xfgon started event.
        $event = \mod_xfgon\event\xfgon_started::create(array(
            'objectid' => $this->properties()->id,
            'context' => context_module::instance($cm->id),
            'courseid' => $this->properties()->course
        ));
        $event->trigger();

        $USER->startxfgon[$this->properties->id] = true;

        $timenow = time();
        $startxfgon = new stdClass;
        $startxfgon->xfgonid = $this->properties->id;
        $startxfgon->userid = $USER->id;
        $startxfgon->starttime = $timenow;
        $startxfgon->xfgontime = $timenow;
        if (WS_SERVER) {
            $startxfgon->timemodifiedoffline = $timenow;
        }
        $DB->insert_record('xfgon_timer', $startxfgon);
        if ($this->properties->timelimit) {
            $this->add_message(get_string('timelimitwarning', 'xfgon', format_time($this->properties->timelimit)), 'center');
        }
        return true;
    }

    /**
     * Updates the timer to the current time and returns the new timer object
     * @param bool $restart If set to true the timer is restarted
     * @param bool $continue If set to true AND $restart=true then the timer
     *                        will continue from a previous attempt
     * @return stdClass The new timer
     */
    public function update_timer($restart=false, $continue=false, $endreached =false) {
        global $USER, $DB;

        $cm = get_coursemodule_from_instance('xfgon', $this->properties->id, $this->properties->course);

        // clock code
        // get time information for this user
        if (!$timer = $this->get_user_timers($USER->id, 'starttime DESC', '*', 0, 1)) {
            $this->start_timer();
            $timer = $this->get_user_timers($USER->id, 'starttime DESC', '*', 0, 1);
        }
        $timer = current($timer); // This will get the latest start time record.

        if ($restart) {
            if ($continue) {
                // continue a previous test, need to update the clock  (think this option is disabled atm)
                $timer->starttime = time() - ($timer->xfgontime - $timer->starttime);

                // Trigger xfgon resumed event.
                $event = \mod_xfgon\event\xfgon_resumed::create(array(
                    'objectid' => $this->properties->id,
                    'context' => context_module::instance($cm->id),
                    'courseid' => $this->properties->course
                ));
                $event->trigger();

            } else {
                // starting over, so reset the clock
                $timer->starttime = time();

                // Trigger xfgon restarted event.
                $event = \mod_xfgon\event\xfgon_restarted::create(array(
                    'objectid' => $this->properties->id,
                    'context' => context_module::instance($cm->id),
                    'courseid' => $this->properties->course
                ));
                $event->trigger();

            }
        }

        $timenow = time();
        $timer->xfgontime = $timenow;
        if (WS_SERVER) {
            $timer->timemodifiedoffline = $timenow;
        }
        $timer->completed = $endreached;
        $DB->update_record('xfgon_timer', $timer);

        // Update completion state.
        $cm = get_coursemodule_from_instance('xfgon', $this->properties()->id, $this->properties()->course,
            false, MUST_EXIST);
        $course = get_course($cm->course);
        $completion = new completion_info($course);
        if ($completion->is_enabled($cm) && $this->properties()->completiontimespent > 0) {
            $completion->update_state($cm, COMPLETION_COMPLETE);
        }
        return $timer;
    }

    /**
     * Updates the timer to the current time then stops it by unsetting the user var
     * @return bool Returns true
     */
    public function stop_timer() {
        global $USER, $DB;
        unset($USER->startxfgon[$this->properties->id]);

        $cm = get_coursemodule_from_instance('xfgon', $this->properties()->id, $this->properties()->course,
            false, MUST_EXIST);

        // Trigger xfgon ended event.
        $event = \mod_xfgon\event\xfgon_ended::create(array(
            'objectid' => $this->properties()->id,
            'context' => context_module::instance($cm->id),
            'courseid' => $this->properties()->course
        ));
        $event->trigger();

        return $this->update_timer(false, false, true);
    }

    /**
     * Checks to see if the xfgon has pages
     */
    public function has_pages() {
        global $DB;
        $pagecount = $DB->count_records('xfgon_pages', array('xfgonid'=>$this->properties->id));
        return ($pagecount>0);
    }

    /**
     * Returns the link for the related activity
     * @return string
     */
    public function link_for_activitylink() {
        global $DB;
        $module = $DB->get_record('course_modules', array('id' => $this->properties->activitylink));
        if ($module) {
            print_r("Goo!!");
            $modname = $DB->get_field('modules', 'name', array('id' => $module->module));
            print_r("Come!!");
            if ($modname) {
                $instancename = $DB->get_field($modname, 'name', array('id' => $module->instance));
                if ($instancename) {
                    return html_writer::link(new moodle_url('/mod/'.$modname.'/view.php',
                        array('id' => $this->properties->activitylink)), get_string('activitylinkname',
                        'xfgon', $instancename), array('class' => 'centerpadded xfgonbutton standardbutton p-r-1'));
                }
            }
        }
        return '';
    }

    /**
     * Loads the requested page.
     *
     * This function will return the requested page id as either a specialised
     * xfgon_page object OR as a generic xfgon_page.
     * If the page has been loaded previously it will be returned from the pages
     * array, otherwise it will be loaded from the database first
     *
     * @param int $pageid
     * @return xfgon_page A xfgon_page object or an object that extends it
     */
    public function load_page($pageid) {
        if (!array_key_exists($pageid, $this->pages)) {
            $manager = xfgon_page_type_manager::get($this);
            $this->pages[$pageid] = $manager->load_page($pageid, $this);
        }
        return $this->pages[$pageid];
    }

    /**
     * Loads ALL of the pages for this xfgon
     *
     * @return array An array containing all pages from this xfgon
     */
    public function load_all_pages() {
        if (!$this->loadedallpages) {
            $manager = xfgon_page_type_manager::get($this);
            $this->pages = $manager->load_all_pages($this);
            $this->loadedallpages = true;
        }
        return $this->pages;
    }

    /**
     * Duplicate the xfgon page.
     *
     * @param  int $pageid Page ID of the page to duplicate.
     * @return void.
     */
    public function duplicate_page($pageid) {
        global $PAGE;
        $cm = get_coursemodule_from_instance('xfgon', $this->properties->id, $this->properties->course);
        $context = context_module::instance($cm->id);
        // Load the page.
        $page = $this->load_page($pageid);
        $properties = $page->properties();
        // The create method checks to see if these properties are set and if not sets them to zero, hence the unsetting here.
        if (!$properties->qoption) {
            unset($properties->qoption);
        }
        if (!$properties->layout) {
            unset($properties->layout);
        }
        if (!$properties->display) {
            unset($properties->display);
        }

        $properties->pageid = $pageid;
        // Add text and format into the format required to create a new page.
        $properties->contents_editor = array(
            'text' => $properties->contents,
            'format' => $properties->contentsformat
        );
        $answers = $page->get_answers();
        // Answers need to be added to $properties.
        $i = 0;
        $answerids = array();
        foreach ($answers as $answer) {
            // Needs to be rearranged to work with the create function.
            $properties->answer_editor[$i] = array(
                'text' => $answer->answer,
                'format' => $answer->answerformat
            );

            $properties->response_editor[$i] = array(
              'text' => $answer->response,
              'format' => $answer->responseformat
            );
            $answerids[] = $answer->id;

            $properties->jumpto[$i] = $answer->jumpto;
            $properties->score[$i] = $answer->score;

            $i++;
        }
        // Create the duplicate page.
        $newxfgonpage = xfgon_page::create($properties, $this, $context, $PAGE->course->maxbytes);
        $newanswers = $newxfgonpage->get_answers();
        // Copy over the file areas as well.
        $this->copy_page_files('page_contents', $pageid, $newxfgonpage->id, $context->id);
        $j = 0;
        foreach ($newanswers as $answer) {
            if (isset($answer->answer) && strpos($answer->answer, '@@PLUGINFILE@@') !== false) {
                $this->copy_page_files('page_answers', $answerids[$j], $answer->id, $context->id);
            }
            if (isset($answer->response) && !is_array($answer->response) && strpos($answer->response, '@@PLUGINFILE@@') !== false) {
                $this->copy_page_files('page_responses', $answerids[$j], $answer->id, $context->id);
            }
            $j++;
        }
    }

    /**
     * Copy the files from one page to another.
     *
     * @param  string $filearea Area that the files are stored.
     * @param  int $itemid Item ID.
     * @param  int $newitemid The item ID for the new page.
     * @param  int $contextid Context ID for this page.
     * @return void.
     */
    protected function copy_page_files($filearea, $itemid, $newitemid, $contextid) {
        $fs = get_file_storage();
        $files = $fs->get_area_files($contextid, 'mod_xfgon', $filearea, $itemid);
        foreach ($files as $file) {
            $fieldupdates = array('itemid' => $newitemid);
            $fs->create_file_from_storedfile($fieldupdates, $file);
        }
    }

    /**
     * Determines if a jumpto value is correct or not.
     *
     * returns true if jumpto page is (logically) after the pageid page or
     * if the jumpto value is a special value.  Returns false in all other cases.
     *
     * @param int $pageid Id of the page from which you are jumping from.
     * @param int $jumpto The jumpto number.
     * @return boolean True or false after a series of tests.
     **/
    public function jumpto_is_correct($pageid, $jumpto) {
        global $DB;

        // first test the special values
        if (!$jumpto) {
            // same page
            return false;
        } elseif ($jumpto == xfgon_NEXTPAGE) {
            return true;
        } elseif ($jumpto == xfgon_UNSEENBRANCHPAGE) {
            return true;
        } elseif ($jumpto == xfgon_RANDOMPAGE) {
            return true;
        } elseif ($jumpto == xfgon_CLUSTERJUMP) {
            return true;
        } elseif ($jumpto == xfgon_EOL) {
            return true;
        }

        $pages = $this->load_all_pages();
        $apageid = $pages[$pageid]->nextpageid;
        while ($apageid != 0) {
            if ($jumpto == $apageid) {
                return true;
            }
            $apageid = $pages[$apageid]->nextpageid;
        }
        return false;
    }

    /**
     * Returns the time a user has remaining on this xfgon
     * @param int $starttime Starttime timestamp
     * @return string
     */
    public function time_remaining($starttime) {
        $timeleft = $starttime + $this->properties->timelimit - time();
        $hours = floor($timeleft/3600);
        $timeleft = $timeleft - ($hours * 3600);
        $minutes = floor($timeleft/60);
        $secs = $timeleft - ($minutes * 60);

        if ($minutes < 10) {
            $minutes = "0$minutes";
        }
        if ($secs < 10) {
            $secs = "0$secs";
        }
        $output   = array();
        $output[] = $hours;
        $output[] = $minutes;
        $output[] = $secs;
        $output = implode(':', $output);
        return $output;
    }

    /**
     * Interprets xfgon_CLUSTERJUMP jumpto value.
     *
     * This will select a page randomly
     * and the page selected will be inbetween a cluster page and end of clutter or end of xfgon
     * and the page selected will be a page that has not been viewed already
     * and if any pages are within a branch table or end of branch then only 1 page within
     * the branch table or end of branch will be randomly selected (sub clustering).
     *
     * @param int $pageid Id of the current page from which we are jumping from.
     * @param int $userid Id of the user.
     * @return int The id of the next page.
     **/
    public function cluster_jump($pageid, $userid=null) {
        global $DB, $USER;

        if ($userid===null) {
            $userid = $USER->id;
        }
        // get the number of retakes
        if (!$retakes = $DB->count_records("xfgon_grades", array("xfgonid"=>$this->properties->id, "userid"=>$userid))) {
            $retakes = 0;
        }
        // get all the xfgon_attempts aka what the user has seen
        $seenpages = array();
        // if ($attempts = $this->get_attempts($retakes)) {
        //     foreach ($attempts as $attempt) {
        //         $seenpages[$attempt->pageid] = $attempt->pageid;
        //     }
        //
        // }

        // get the xfgon pages
        $xfgonpages = $this->load_all_pages();
        // find the start of the cluster
        while ($pageid != 0) { // this condition should not be satisfied... should be a cluster page
            if ($xfgonpages[$pageid]->qtype == xfgon_PAGE_CLUSTER) {
                break;
            }
            $pageid = $xfgonpages[$pageid]->prevpageid;
        }

        $clusterpages = array();
        $clusterpages = $this->get_sub_pages_of($pageid, array(xfgon_PAGE_ENDOFCLUSTER));
        $unseen = array();
        foreach ($clusterpages as $key=>$cluster) {
            // Remove the page if  it is in a branch table or is an endofbranch.
            if ($this->is_sub_page_of_type($cluster->id,
                    array(xfgon_PAGE_BRANCHTABLE), array(xfgon_PAGE_ENDOFBRANCH, xfgon_PAGE_CLUSTER))
                    || $cluster->qtype == xfgon_PAGE_ENDOFBRANCH) {
                unset($clusterpages[$key]);
            } else if ($cluster->qtype == xfgon_PAGE_BRANCHTABLE) {
                // If branchtable, check to see if any pages inside have been viewed.
                $branchpages = $this->get_sub_pages_of($cluster->id, array(xfgon_PAGE_BRANCHTABLE, xfgon_PAGE_ENDOFBRANCH));
                $flag = true;
                foreach ($branchpages as $branchpage) {
                    if (array_key_exists($branchpage->id, $seenpages)) {  // Check if any of the pages have been viewed.
                        $flag = false;
                    }
                }
                if ($flag && count($branchpages) > 0) {
                    // Add branch table.
                    $unseen[] = $cluster;
                }
            } elseif ($cluster->is_unseen($seenpages)) {
                $unseen[] = $cluster;
            }
        }

        if (count($unseen) > 0) {
            // it does not contain elements, then use exitjump, otherwise find out next page/branch
            $nextpage = $unseen[rand(0, count($unseen)-1)];
            if ($nextpage->qtype == xfgon_PAGE_BRANCHTABLE) {
                // if branch table, then pick a random page inside of it
                $branchpages = $this->get_sub_pages_of($nextpage->id, array(xfgon_PAGE_BRANCHTABLE, xfgon_PAGE_ENDOFBRANCH));
                return $branchpages[rand(0, count($branchpages)-1)]->id;
            } else { // otherwise, return the page's id
                return $nextpage->id;
            }
        } else {
            // seen all there is to see, leave the cluster
            if (end($clusterpages)->nextpageid == 0) {
                return xfgon_EOL;
            } else {
                $clusterendid = $pageid;
                while ($clusterendid != 0) { // This condition should not be satisfied... should be an end of cluster page.
                    if ($xfgonpages[$clusterendid]->qtype == xfgon_PAGE_ENDOFCLUSTER) {
                        break;
                    }
                    $clusterendid = $xfgonpages[$clusterendid]->nextpageid;
                }
                $exitjump = $DB->get_field("xfgon_answers", "jumpto", array("pageid" => $clusterendid, "xfgonid" => $this->properties->id));
                if ($exitjump == xfgon_NEXTPAGE) {
                    $exitjump = $xfgonpages[$clusterendid]->nextpageid;
                }
                if ($exitjump == 0) {
                    return xfgon_EOL;
                } else if (in_array($exitjump, array(xfgon_EOL, xfgon_PREVIOUSPAGE))) {
                    return $exitjump;
                } else {
                    if (!array_key_exists($exitjump, $xfgonpages)) {
                        $found = false;
                        foreach ($xfgonpages as $page) {
                            if ($page->id === $clusterendid) {
                                $found = true;
                            } else if ($page->qtype == xfgon_PAGE_ENDOFCLUSTER) {
                                $exitjump = $DB->get_field("xfgon_answers", "jumpto", array("pageid" => $page->id, "xfgonid" => $this->properties->id));
                                if ($exitjump == xfgon_NEXTPAGE) {
                                    $exitjump = $xfgonpages[$page->id]->nextpageid;
                                }
                                break;
                            }
                        }
                    }
                    if (!array_key_exists($exitjump, $xfgonpages)) {
                        return xfgon_EOL;
                    }
                    // Check to see that the return type is not a cluster.
                    if ($xfgonpages[$exitjump]->qtype == xfgon_PAGE_CLUSTER) {
                        // If the exitjump is a cluster then go through this function again and try to find an unseen question.
                        $exitjump = $this->cluster_jump($exitjump, $userid);
                    }
                    return $exitjump;
                }
            }
        }
    }

    /**
     * Finds all pages that appear to be a subtype of the provided pageid until
     * an end point specified within $ends is encountered or no more pages exist
     *
     * @param int $pageid
     * @param array $ends An array of xfgon_PAGE_* types that signify an end of
     *               the subtype
     * @return array An array of specialised xfgon_page objects
     */
    public function get_sub_pages_of($pageid, array $ends) {
        $xfgonpages = $this->load_all_pages();
        $pageid = $xfgonpages[$pageid]->nextpageid;  // move to the first page after the branch table
        $pages = array();

        while (true) {
            if ($pageid == 0 || in_array($xfgonpages[$pageid]->qtype, $ends)) {
                break;
            }
            $pages[] = $xfgonpages[$pageid];
            $pageid = $xfgonpages[$pageid]->nextpageid;
        }

        return $pages;
    }

    /**
     * Checks to see if the specified page[id] is a subpage of a type specified in
     * the $types array, until either there are no more pages of we find a type
     * corresponding to that of a type specified in $ends
     *
     * @param int $pageid The id of the page to check
     * @param array $types An array of types that would signify this page was a subpage
     * @param array $ends An array of types that mean this is not a subpage
     * @return bool
     */
    public function is_sub_page_of_type($pageid, array $types, array $ends) {
        $pages = $this->load_all_pages();
        $pageid = $pages[$pageid]->prevpageid; // move up one

        array_unshift($ends, 0);
        // go up the pages till branch table
        while (true) {
            if ($pageid==0 || in_array($pages[$pageid]->qtype, $ends)) {
                return false;
            } else if (in_array($pages[$pageid]->qtype, $types)) {
                return true;
            }
            $pageid = $pages[$pageid]->prevpageid;
        }
    }

    /**
     * Move a page resorting all other pages.
     *
     * @param int $pageid
     * @param int $after
     * @return void
     */
    public function resort_pages($pageid, $after) {
        global $CFG;

        $cm = get_coursemodule_from_instance('xfgon', $this->properties->id, $this->properties->course);
        $context = context_module::instance($cm->id);

        $pages = $this->load_all_pages();

        if (!array_key_exists($pageid, $pages) || ($after!=0 && !array_key_exists($after, $pages))) {
            print_error('cannotfindpages', 'xfgon', "$CFG->wwwroot/mod/xfgon/edit.php?id=$cm->id");
        }

        $pagetomove = clone($pages[$pageid]);
        unset($pages[$pageid]);

        $pageids = array();
        if ($after === 0) {
            $pageids['p0'] = $pageid;
        }
        foreach ($pages as $page) {
            $pageids[] = $page->id;
            if ($page->id == $after) {
                $pageids[] = $pageid;
            }
        }

        $pageidsref = $pageids;
        reset($pageidsref);
        $prev = 0;
        $next = next($pageidsref);
        foreach ($pageids as $pid) {
            if ($pid === $pageid) {
                $page = $pagetomove;
            } else {
                $page = $pages[$pid];
            }
            if ($page->prevpageid != $prev || $page->nextpageid != $next) {
                $page->move($next, $prev);

                if ($pid === $pageid) {
                    // We will trigger an event.
                    $pageupdated = array('next' => $next, 'prev' => $prev);
                }
            }

            $prev = $page->id;
            $next = next($pageidsref);
            if (!$next) {
                $next = 0;
            }
        }

        // Trigger an event: page moved.
        if (!empty($pageupdated)) {
            $eventparams = array(
                'context' => $context,
                'objectid' => $pageid,
                'other' => array(
                    'pagetype' => $page->get_typestring(),
                    'prevpageid' => $pageupdated['prev'],
                    'nextpageid' => $pageupdated['next']
                )
            );
            $event = \mod_xfgon\event\page_moved::create($eventparams);
            $event->trigger();
        }

    }

    /**
     * Return the xfgon context object.
     *
     * @return stdClass context
     * @since  Moodle 3.3
     */
    public function get_context() {
        if ($this->context == null) {
            $this->context = context_module::instance($this->get_cm()->id);
        }
        return $this->context;
    }

    /**
     * Set the xfgon course module object.
     *
     * @param stdClass $cm course module objct
     * @since  Moodle 3.3
     */
    private function set_cm($cm) {
        $this->cm = $cm;
    }

    /**
     * Return the xfgon course module object.
     *
     * @return stdClass course module
     * @since  Moodle 3.3
     */
    public function get_cm() {
        if ($this->cm == null) {
            $this->cm = get_coursemodule_from_instance('xfgon', $this->properties->id);
        }
        return $this->cm;
    }

    /**
     * Set the xfgon course object.
     *
     * @param stdClass $course course objct
     * @since  Moodle 3.3
     */
    private function set_courserecord($course) {
        $this->courserecord = $course;
    }

    /**
     * Return the xfgon course object.
     *
     * @return stdClass course
     * @since  Moodle 3.3
     */
    public function get_courserecord() {
        global $DB;

        if ($this->courserecord == null) {
            $this->courserecord = $DB->get_record('course', array('id' => $this->properties->course));
        }
        return $this->courserecord;
    }

    /**
     * Check if the user can manage the xfgon activity.
     *
     * @return bool true if the user can manage the xfgon
     * @since  Moodle 3.3
     */
    public function can_manage() {
        return has_capability('mod/xfgon:manage', $this->get_context());
    }

    /**
     * Check if time restriction is applied.
     *
     * @return mixed false if  there aren't restrictions or an object with the restriction information
     * @since  Moodle 3.3
     */
    public function get_time_restriction_status() {
        if ($this->can_manage()) {
            return false;
        }

        if (!$this->is_accessible()) {
            if ($this->properties->deadline != 0 && time() > $this->properties->deadline) {
                $status = ['reason' => 'xfgonclosed', 'time' => $this->properties->deadline];
            } else {
                $status = ['reason' => 'xfgonopen', 'time' => $this->properties->available];
            }
            return (object) $status;
        }
        return false;
    }


    /**
     * Return the number of retries in a xfgon for a given user.
     *
     * @param  int $userid the user id
     * @return int the retries count
     * @since  Moodle 3.3
     */
    public function count_user_retries($userid) {
        global $DB;

        return $DB->count_records('xfgon_grades', array("xfgonid" => $this->properties->id, "userid" => $userid));
    }

    /**
     * Check if a user left a timed session.
     *
     * @param int $retriescount the number of retries for the xfgon (the last retry number).
     * @return true if the user left the timed session
     * @since  Moodle 3.3
     */
    public function left_during_timed_session($retriescount) {
        global $DB, $USER;

        $conditions = array('xfgonid' => $this->properties->id, 'userid' => $USER->id, 'retry' => $retriescount);
        return $DB->count_records('xfgon_attempts', $conditions) > 0 || $DB->count_records('xfgon_branch', $conditions) > 0;
    }

    /**
     * Trigger module viewed event and set the module viewed for completion.
     *
     * @since  Moodle 3.3
     */
    public function set_module_viewed() {
        global $CFG;
        require_once($CFG->libdir . '/completionlib.php');

        // Trigger module viewed event.
        $event = \mod_xfgon\event\course_module_viewed::create(array(
            'objectid' => $this->properties->id,
            'context' => $this->get_context()
        ));
        $event->add_record_snapshot('course_modules', $this->get_cm());
        $event->add_record_snapshot('course', $this->get_courserecord());
        $event->trigger();

        // Mark as viewed.
        $completion = new completion_info($this->get_courserecord());
        $completion->set_module_viewed($this->get_cm());
    }

    /**
     * Return the timers in the current xfgon for the given user.
     *
     * @param  int      $userid    the user id
     * @param  string   $sort      an order to sort the results in (optional, a valid SQL ORDER BY parameter).
     * @param  string   $fields    a comma separated list of fields to return
     * @param  int      $limitfrom return a subset of records, starting at this point (optional).
     * @param  int      $limitnum  return a subset comprising this many records in total (optional, required if $limitfrom is set).
     * @return array    list of timers for the given user in the xfgon
     * @since  Moodle 3.3
     */
    public function get_user_timers($userid = null, $sort = '', $fields = '*', $limitfrom = 0, $limitnum = 0) {
        global $DB, $USER;

        if ($userid === null) {
            $userid = $USER->id;
        }

        $params = array('xfgonid' => $this->properties->id, 'userid' => $userid);
        return $DB->get_records('xfgon_timer', $params, $sort, $fields, $limitfrom, $limitnum);
    }

    /**
     * Check if the user is out of time in a timed xfgon.
     *
     * @param  stdClass $timer timer object
     * @return bool True if the user is on time, false is the user ran out of time
     * @since  Moodle 3.3
     */
    public function check_time($timer) {
        if ($this->properties->timelimit) {
            $timeleft = $timer->starttime + $this->properties->timelimit - time();
            if ($timeleft <= 0) {
                // Out of time.
                $this->add_message(get_string('eolstudentoutoftime', 'xfgon'));
                return false;
            } else if ($timeleft < 60) {
                // One minute warning.
                $this->add_message(get_string('studentoneminwarning', 'xfgon'));
            }
        }
        return true;
    }


    /**
     * Get the ongoing score message for the user (depending on the user permission and xfgon settings).
     *
     * @return str the ongoing score message
     * @since  Moodle 3.3
     */
    public function get_ongoing_score_message() {
        global $USER, $DB;

        $context = $this->get_context();

        if (has_capability('mod/xfgon:manage', $context)) {
            return get_string('teacherongoingwarning', 'xfgon');
        } else {
            $ntries = $DB->count_records("xfgon_grades", array("xfgonid" => $this->properties->id, "userid" => $USER->id));
            if (isset($USER->modattempts[$this->properties->id])) {
                $ntries--;
            }
            $gradeinfo = xfgon_grade($this, $ntries);
            $a = new stdClass;
            if ($this->properties->custom) {
                $a->score = $gradeinfo->earned;
                $a->currenthigh = $gradeinfo->total;
                return get_string("ongoingcustom", "xfgon", $a);
            } else {
                $a->correct = $gradeinfo->earned;
                $a->viewed = $gradeinfo->attempts;
                return get_string("ongoingnormal", "xfgon", $a);
            }
        }
    }

    /**
     * Calculate the progress of the current user in the xfgon.
     *
     * @return int the progress (scale 0-100)
     * @since  Moodle 3.3
     */
    public function calculate_progress() {
        global $USER, $DB;

        // Check if the user is reviewing the attempt.
        if (isset($USER->modattempts[$this->properties->id])) {
            return 100;
        }

        $progress = 100;
        return (int) $progress;
    }


}


/**
 * Abstract class to provide a core functions to the all xfgon classes
 *
 * This class should be abstracted by ALL classes with the xfgon module to ensure
 * that all classes within this module can be interacted with in the same way.
 *
 * This class provides the user with a basic properties array that can be fetched
 * or set via magic methods, or alternatively by defining methods get_blah() or
 * set_blah() within the extending object.
 *
 * @copyright  2009 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class xfgon_base {

    /**
     * An object containing properties
     * @var stdClass
     */
    protected $properties;

    /**
     * The constructor
     * @param stdClass $properties
     */
    public function __construct($properties) {
        $this->properties = (object)$properties;
    }

    /**
     * Magic property method
     *
     * Attempts to call a set_$key method if one exists otherwise falls back
     * to simply set the property
     *
     * @param string $key
     * @param mixed $value
     */
    public function __set($key, $value) {
        if (method_exists($this, 'set_'.$key)) {
            $this->{'set_'.$key}($value);
        }
        $this->properties->{$key} = $value;
    }

    /**
     * Magic get method
     *
     * Attempts to call a get_$key method to return the property and ralls over
     * to return the raw property
     *
     * @param str $key
     * @return mixed
     */
    public function __get($key) {
        if (method_exists($this, 'get_'.$key)) {
            return $this->{'get_'.$key}();
        }
        return $this->properties->{$key};
    }

    /**
     * Stupid PHP needs an isset magic method if you use the get magic method and
     * still want empty calls to work.... blah ~!
     *
     * @param string $key
     * @return bool
     */
    public function __isset($key) {
        if (method_exists($this, 'get_'.$key)) {
            $val = $this->{'get_'.$key}();
            return !empty($val);
        }
        return !empty($this->properties->{$key});
    }

    //NOTE: E_STRICT does not allow to change function signature!

    /**
     * If implemented should create a new instance, save it in the DB and return it
     */
    //public static function create() {}
    /**
     * If implemented should load an instance from the DB and return it
     */
    //public static function load() {}
    /**
     * Fetches all of the properties of the object
     * @return stdClass
     */
    public function properties() {
        return $this->properties;
    }
}


/**
 * Abstract class representation of a page associated with a xfgon.
 *
 * This class should MUST be extended by all specialised page types defined in
 * mod/xfgon/pagetypes/.
 * There are a handful of abstract methods that need to be defined as well as
 * severl methods that can optionally be defined in order to make the page type
 * operate in the desired way
 *
 * Database properties
 * @property int $id The id of this xfgon page
 * @property int $xfgonid The id of the xfgon this page belongs to
 * @property int $prevpageid The id of the page before this one
 * @property int $nextpageid The id of the next page in the page sequence
 * @property int $qtype Identifies the page type of this page
 * @property int $qoption Used to record page type specific options
 * @property int $layout Used to record page specific layout selections
 * @property int $display Used to record page specific display selections
 * @property int $timecreated Timestamp for when the page was created
 * @property int $timemodified Timestamp for when the page was last modified
 * @property string $title The title of this page
 * @property string $contents The rich content shown to describe the page
 * @property int $contentsformat The format of the contents field
 *
 * Calculated properties
 * @property-read array $answers An array of answers for this page
 * @property-read bool $displayinmenublock Toggles display in the left menu block
 * @property-read array $jumps An array containing all the jumps this page uses
 * @property-read xfgon $xfgon The xfgon this page belongs to
 * @property-read int $type The type of the page [question | structure]
 * @property-read typeid The unique identifier for the page type
 * @property-read typestring The string that describes this page type
 *
 * @abstract
 * @copyright  2009 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class xfgon_page extends xfgon_base {

    /**
     * A reference to the xfgon this page belongs to
     * @var xfgon
     */
    protected $xfgon = null;
    /**
     * Contains the answers to this xfgon_page once loaded
     * @var null|array
     */
    protected $answers = null;
    /**
     * This sets the type of the page, can be one of the constants defined below
     * @var int
     */
    protected $type = 0;

    /**
     * Constants used to identify the type of the page
     */
    const TYPE_QUESTION = 0;
    const TYPE_STRUCTURE = 1;

    /**
     * Constant used as a delimiter when parsing multianswer questions
     */
    const MULTIANSWER_DELIMITER = '@^#|';

    /**
     * This method should return the integer used to identify the page type within
     * the database and throughout code. This maps back to the defines used in 1.x
     * @abstract
     * @return int
     */
    abstract protected function get_typeid();
    /**
     * This method should return the string that describes the pagetype
     * @abstract
     * @return string
     */
    abstract protected function get_typestring();

    /**
     * This method gets called to display the page to the user taking the xfgon
     * @abstract
     * @param object $renderer
     * @param object $attempt
     * @return string
     */
    abstract public function display($renderer, $attempt);

    /**
     * Creates a new xfgon_page within the database and returns the correct pagetype
     * object to use to interact with the new xfgon
     *
     * @final
     * @static
     * @param object $properties
     * @param xfgon $xfgon
     * @return xfgon_page Specialised object that extends xfgon_page
     */
    final public static function create($properties, xfgon $xfgon, $context, $maxbytes) {
        global $DB;
        $newpage = new stdClass;
        $newpage->title = $properties->title;
        $newpage->contents = $properties->contents_editor['text'];
        $newpage->contentsformat = $properties->contents_editor['format'];
        $newpage->xfgonid = $xfgon->id;
        $newpage->timecreated = time();
        $newpage->qtype = $properties->qtype;
        $newpage->qoption = (isset($properties->qoption))?1:0;
        $newpage->layout = (isset($properties->layout))?1:0;
        $newpage->display = (isset($properties->display))?1:0;
        $newpage->prevpageid = 0; // this is a first page
        $newpage->nextpageid = 0; // this is the only page

        $editor = new stdClass;
        $editor->id = $newpage->id;
        $editor->contents_editor = $properties->contents_editor;
        $editor = file_postupdate_standard_editor($editor, 'contents', array('noclean'=>true, 'maxfiles'=>EDITOR_UNLIMITED_FILES, 'maxbytes'=>$maxbytes), $context, 'mod_xfgon', 'page_contents', $editor->id);

        $page = xfgon_page::load($newpage, $xfgon);
        $page->create_answers($properties);

        // Trigger an event: page created.
        $eventparams = array(
            'context' => $context,
            'objectid' => $newpage->id,
            'other' => array(
                'pagetype' => $page->get_typestring()
                )
            );
        $event = \mod_xfgon\event\page_created::create($eventparams);
        $snapshot = clone($newpage);
        $snapshot->timemodified = 0;
        $event->add_record_snapshot('xfgon_pages', $snapshot);
        $event->trigger();

        $xfgon->add_message(get_string('insertedpage', 'xfgon').': '.format_string($newpage->title, true), 'notifysuccess');

        return $page;
    }

    /**
     * This method loads a page object from the database and returns it as a
     * specialised object that extends xfgon_page
     *
     * @final
     * @static
     * @param int $id
     * @param xfgon $xfgon
     * @return xfgon_page Specialised xfgon_page object
     */
    final public static function load($id, xfgon $xfgon) {
        global $DB;
        return new $class($page, $xfgon);
    }

    /**
     * Deletes a xfgon_page from the database as well as any associated records.
     * @final
     * @return bool
     */
    final public function delete() {
        global $DB;

        $cm = get_coursemodule_from_instance('xfgon', $this->xfgon->id, $this->xfgon->course);
        $context = context_module::instance($cm->id);

        // Delete files associated with attempts.
        $fs = get_file_storage();

        // Trigger an event: page deleted.
        $eventparams = array(
            'context' => $context,
            'objectid' => $this->properties->id,
            'other' => array(
                'pagetype' => $this->get_typestring()
                )
            );
        $event = \mod_xfgon\event\page_deleted::create($eventparams);
        $event->add_record_snapshot('xfgon_pages', $this->properties);
        $event->trigger();

        // Delete files associated with this page.
        $fs->delete_area_files($context->id, 'mod_xfgon', 'page_contents', $this->properties->id);

        return true;
    }

    /**
     * Moves a page by updating its nextpageid and prevpageid values within
     * the database
     *
     * @final
     * @param int $nextpageid
     * @param int $prevpageid
     */
    final public function move($nextpageid=null, $prevpageid=null) {
        global $DB;
        // To be improved...
    }

    /**
     * Returns the answers that are associated with this page in the database
     *
     * @final
     * @return array
     */
    final public function get_answers() {
        global $DB;
        return $this->answers;
    }

    /**
     * Returns the xfgon this page is associated with
     * @final
     * @return xfgon
     */
    final protected function get_xfgon() {
        return $this->xfgon;
    }

    /**
     * Returns the type of page this is. Not to be confused with page type
     * @final
     * @return int
     */
    final protected function get_type() {
        return $this->type;
    }

    /**
     * Records an attempt at this page
     *
     * @final
     * @global moodle_database $DB
     * @param stdClass $context
     * @return stdClass Returns the result of the attempt
     */
    final public function record_attempt($context) {
        global $DB, $USER, $OUTPUT, $PAGE;

        /**
         * This should be overridden by each page type to actually check the response
         * against what ever custom criteria they have defined
         */
        $result = $this->check_answer();

        // Processes inmediate jumps.
        if ($result->inmediatejump) {
            return $result;
        }

        $result->attemptsremaining  = 0;
        $result->maxattemptsreached = false;

        if ($result->noanswer) {
            $result->newpageid = $this->properties->id; // display same page again
            $result->feedback  = get_string('noanswer', 'xfgon');
        } else {

            // Determine default feedback if necessary
            if (empty($result->response)) {
                if (!$this->xfgon->feedback && !$result->noanswer && !($this->xfgon->review & !$result->correctanswer && !$result->isessayquestion)) {
                    // These conditions have been met:
                    //  1. The xfgon manager has not supplied feedback to the student
                    //  2. Not displaying default feedback
                    //  3. The user did provide an answer
                    //  4. We are not reviewing with an incorrect answer (and not reviewing an essay question)

                    $result->nodefaultresponse = true;  // This will cause a redirect below
                } else if ($result->isessayquestion) {
                    $result->response = get_string('defaultessayresponse', 'xfgon');
                } else if ($result->correctanswer) {
                    $result->response = get_string('thatsthecorrectanswer', 'xfgon');
                } else {
                    $result->response = get_string('thatsthewronganswer', 'xfgon');
                }
            }

            if ($result->response) {
                if ($this->xfgon->review && !$result->correctanswer && !$result->isessayquestion) {

                } else {
                    $result->feedback = '';
                }
                $class = 'response';
                if ($result->correctanswer) {
                    $class .= ' correct'; // CSS over-ride this if they exist (!important).
                } else if (!$result->isessayquestion) {
                    $class .= ' incorrect'; // CSS over-ride this if they exist (!important).
                }
                $options = new stdClass;
                $options->noclean = true;
                $options->para = true;
                $options->overflowdiv = true;
                $options->context = $context;

                $result->feedback .= $OUTPUT->box(format_text($this->get_contents(), $this->properties->contentsformat, $options),
                        'generalbox boxaligncenter p-y-1');
                $result->feedback .= '<div class="correctanswer generalbox"><em>'
                        . get_string("youranswer", "xfgon").'</em> : <div class="studentanswer m-t-2 m-b-2">';

                // Create a table containing the answers and responses.
                $table = new html_table();
                // Multianswer allowed.
                if ($this->properties->qoption) {
                    $studentanswerarray = explode(self::MULTIANSWER_DELIMITER, $result->studentanswer);
                    $responsearr = explode(self::MULTIANSWER_DELIMITER, $result->response);
                    $studentanswerresponse = array_combine($studentanswerarray, $responsearr);

                    foreach ($studentanswerresponse as $answer => $response) {
                        // Add a table row containing the answer.
                        $studentanswer = $this->format_answer($answer, $context, $result->studentanswerformat, $options);
                        $table->data[] = array($studentanswer);
                        // If the response exists, add a table row containing the response. If not, add en empty row.
                        if (!empty(trim($response))) {
                            $studentresponse = isset($result->responseformat) ?
                                $this->format_response($response, $context, $result->responseformat, $options) : $response;
                            $studentresponsecontent = html_writer::div('<em>' . get_string("response", "xfgon") .
                                '</em>: <br/>' . $studentresponse, $class);
                            $table->data[] = array($studentresponsecontent);
                        } else {
                            $table->data[] = array('');
                        }
                    }
                } else {
                    // Add a table row containing the answer.
                    $studentanswer = $this->format_answer($result->studentanswer, $context, $result->studentanswerformat, $options);
                    $table->data[] = array($studentanswer);
                    // If the response exists, add a table row containing the response. If not, add en empty row.
                    if (!empty(trim($result->response))) {
                        $studentresponse = isset($result->responseformat) ?
                            $this->format_response($result->response, $context, $result->responseformat,
                                $result->answerid, $options) : $result->response;
                        $studentresponsecontent = html_writer::div('<em>' . get_string("response", "xfgon") .
                            '</em>: <br/>' . $studentresponse, $class);
                        $table->data[] = array($studentresponsecontent);
                    } else {
                        $table->data[] = array('');
                    }
                }

                $result->feedback .= html_writer::table($table).'</div></div>';
            }
        }
        return $result;
    }

    /**
     * Formats the answer
     *
     * @param string $answer
     * @param context $context
     * @param int $answerformat
     * @return string Returns formatted string
     */
    private function format_answer($answer, $context, $answerformat, $options = []) {

        if (empty($options)) {
            $options = [
                'context' => $context,
                'para' => true
            ];
        }
        return format_text($answer, $answerformat, $options);
    }

    /**
     * Formats the response
     *
     * @param string $response
     * @param context $context
     * @param int $responseformat
     * @param int $answerid
     * @param stdClass $options
     * @return string Returns formatted string
     */
    private function format_response($response, $context, $responseformat, $answerid, $options) {

        $convertstudentresponse = file_rewrite_pluginfile_urls($response, 'pluginfile.php',
            $context->id, 'mod_xfgon', 'page_responses', $answerid);

        return format_text($convertstudentresponse, $responseformat, $options);
    }

    /**
     * Returns the string for a jump name
     *
     * @final
     * @param int $jumpto Jump code or page ID
     * @return string
     **/
    final protected function get_jump_name($jumpto) {
        global $DB;
        static $jumpnames = array();

        if (!array_key_exists($jumpto, $jumpnames)) {
            if ($jumpto == xfgon_THISPAGE) {
                $jumptitle = get_string('thispage', 'xfgon');
            } elseif ($jumpto == xfgon_NEXTPAGE) {
                $jumptitle = get_string('nextpage', 'xfgon');
            } elseif ($jumpto == xfgon_EOL) {
                $jumptitle = get_string('endofxfgon', 'xfgon');
            } elseif ($jumpto == xfgon_UNSEENBRANCHPAGE) {
                $jumptitle = get_string('unseenpageinbranch', 'xfgon');
            } elseif ($jumpto == xfgon_PREVIOUSPAGE) {
                $jumptitle = get_string('previouspage', 'xfgon');
            } elseif ($jumpto == xfgon_RANDOMPAGE) {
                $jumptitle = get_string('randompageinbranch', 'xfgon');
            } elseif ($jumpto == xfgon_RANDOMBRANCH) {
                $jumptitle = get_string('randombranch', 'xfgon');
            } elseif ($jumpto == xfgon_CLUSTERJUMP) {
                $jumptitle = get_string('clusterjump', 'xfgon');
            } else {

            }
            $jumpnames[$jumpto] = format_string($jumptitle,true);
        }

        return $jumpnames[$jumpto];
    }

    /**
     * Constructor method
     * @param object $properties
     * @param xfgon $xfgon
     */
    public function __construct($properties, xfgon $xfgon) {
        parent::__construct($properties);
        $this->xfgon = $xfgon;
    }

    /**
     * Returns the score for the attempt
     * This may be overridden by page types that require manual grading
     * @param array $answers
     * @param object $attempt
     * @return int
     */
    public function earned_score($answers, $attempt) {
        return $answers[$attempt->answerid]->score;
    }

    /**
     * This is a callback method that can be override and gets called when ever a page
     * is viewed
     *
     * @param bool $canmanage True if the user has the manage cap
     * @param bool $redirect  Optional, default to true. Set to false to avoid redirection and return the page to redirect.
     * @return mixed
     */
    public function callback_on_view($canmanage, $redirect = true) {
        return true;
    }

    /**
     * save editor answers files and update answer record
     *
     * @param object $context
     * @param int $maxbytes
     * @param object $answer
     * @param object $answereditor
     * @param object $responseeditor
     */
    public function save_answers_files($context, $maxbytes, &$answer, $answereditor = '', $responseeditor = '') {
        global $DB;
        // To be improved...
    }

    /**
     * Rewrite urls in response and optionality answer of a question answer
     *
     * @param object $answer
     * @param bool $rewriteanswer must rewrite answer
     * @return object answer with rewritten urls
     */
    public static function rewrite_answers_urls($answer, $rewriteanswer = true) {
        global $PAGE;

        $context = context_module::instance($PAGE->cm->id);
        if ($rewriteanswer) {
            $answer->answer = file_rewrite_pluginfile_urls($answer->answer, 'pluginfile.php', $context->id,
                    'mod_xfgon', 'page_answers', $answer->id);
        }
        $answer->response = file_rewrite_pluginfile_urls($answer->response, 'pluginfile.php', $context->id,
                'mod_xfgon', 'page_responses', $answer->id);

        return $answer;
    }

    /**
     * Updates a xfgon page and its answers within the database
     *
     * @param object $properties
     * @return bool
     */
    public function update($properties, $context = null, $maxbytes = null) {
        global $DB, $PAGE;
        $answers  = $this->get_answers();
        $properties->id = $this->properties->id;
        $properties->xfgonid = $this->xfgon->id;
        if (empty($properties->qoption)) {
            $properties->qoption = '0';
        }
        if (empty($context)) {
            $context = $PAGE->context;
        }
        if ($maxbytes === null) {
            $maxbytes = get_user_max_upload_file_size($context);
        }
        $properties->timemodified = time();
        $properties = file_postupdate_standard_editor($properties, 'contents', array('noclean'=>true, 'maxfiles'=>EDITOR_UNLIMITED_FILES, 'maxbytes'=>$maxbytes), $context, 'mod_xfgon', 'page_contents', $properties->id);
        // $DB->update_record("xfgon_pages", $properties);

        // Trigger an event: page updated.
        \mod_xfgon\event\page_updated::create_from_xfgon_page($this, $context)->trigger();

        if ($this->type == self::TYPE_STRUCTURE && $this->get_typeid() != xfgon_PAGE_BRANCHTABLE) {
            // These page types have only one answer to save the jump and score.
            if (count($answers) > 1) {
                $answer = array_shift($answers);
            } else if (count($answers) == 1) {
                $answer = array_shift($answers);
            } else {
                $answer = new stdClass;
                $answer->xfgonid = $properties->xfgonid;
                $answer->pageid = $properties->id;
                $answer->timecreated = time();
            }

            $answer->timemodified = time();
            if (isset($properties->jumpto[0])) {
                $answer->jumpto = $properties->jumpto[0];
            }
            if (isset($properties->score[0])) {
                $answer->score = $properties->score[0];
            }

        } else {
            for ($i = 0; $i < $this->xfgon->maxanswers; $i++) {
                if (!array_key_exists($i, $this->answers)) {
                    $this->answers[$i] = new stdClass;
                    $this->answers[$i]->xfgonid = $this->xfgon->id;
                    $this->answers[$i]->pageid = $this->id;
                    $this->answers[$i]->timecreated = $this->timecreated;
                }

                if (isset($properties->answer_editor[$i])) {
                    if (is_array($properties->answer_editor[$i])) {
                        // Multichoice and true/false pages have an HTML editor.
                        $this->answers[$i]->answer = $properties->answer_editor[$i]['text'];
                        $this->answers[$i]->answerformat = $properties->answer_editor[$i]['format'];
                    } else {
                        // Branch tables, shortanswer and mumerical pages have only a text field.
                        $this->answers[$i]->answer = $properties->answer_editor[$i];
                        $this->answers[$i]->answerformat = FORMAT_MOODLE;
                    }
                }

                if (!empty($properties->response_editor[$i]) && is_array($properties->response_editor[$i])) {
                    $this->answers[$i]->response = $properties->response_editor[$i]['text'];
                    $this->answers[$i]->responseformat = $properties->response_editor[$i]['format'];
                }

                if (isset($this->answers[$i]->answer) && $this->answers[$i]->answer != '') {
                    if (isset($properties->jumpto[$i])) {
                        $this->answers[$i]->jumpto = $properties->jumpto[$i];
                    }
                    if ($this->xfgon->custom && isset($properties->score[$i])) {
                        $this->answers[$i]->score = $properties->score[$i];
                    }

                    // Save files in answers and responses.
                    if (isset($properties->response_editor[$i])) {
                        $this->save_answers_files($context, $maxbytes, $this->answers[$i],
                                $properties->answer_editor[$i], $properties->response_editor[$i]);
                    } else {
                        $this->save_answers_files($context, $maxbytes, $this->answers[$i],
                                $properties->answer_editor[$i]);
                    }

                } else if (isset($this->answers[$i]->id)) {
                    unset($this->answers[$i]);
                }
            }
        }
        return true;
    }

    /**
     * Can be set to true if the page requires a static link to create a new instance
     * instead of simply being included in the dropdown
     * @param int $previd
     * @return bool
     */
    public function add_page_link($previd) {
        return false;
    }

    /**
     * Returns true if a page has been viewed before
     *
     * @param array|int $param Either an array of pages that have been seen or the
     *                   number of retakes a user has had
     * @return bool
     */
    public function is_unseen($param) {
        global $USER, $DB;
        if (is_array($param)) {
            $seenpages = $param;
            return (!array_key_exists($this->properties->id, $seenpages));
        } else {
            $nretakes = $param;

        }
        return false;
    }

    /**
     * Checks to see if a page has been answered previously
     * @param int $nretakes
     * @return bool
     */
    public function is_unanswered($nretakes) {
        global $DB, $USER;
        return false;
    }

    /**
     * Creates answers within the database for this xfgon_page. Usually only ever
     * called when creating a new page instance
     * @param object $properties
     * @return array
     */
    public function create_answers($properties) {
        global $DB, $PAGE;
        // now add the answers
        $newanswer = new stdClass;
        $newanswer->xfgonid = $this->xfgon->id;
        $newanswer->pageid = $this->properties->id;
        $newanswer->timecreated = $this->properties->timecreated;

        $cm = get_coursemodule_from_instance('xfgon', $this->xfgon->id, $this->xfgon->course);
        $context = context_module::instance($cm->id);

        $answers = array();

        for ($i = 0; $i < $this->xfgon->maxanswers; $i++) {
            $answer = clone($newanswer);

            if (isset($properties->answer_editor[$i])) {
                if (is_array($properties->answer_editor[$i])) {
                    // Multichoice and true/false pages have an HTML editor.
                    $answer->answer = $properties->answer_editor[$i]['text'];
                    $answer->answerformat = $properties->answer_editor[$i]['format'];
                } else {
                    // Branch tables, shortanswer and mumerical pages have only a text field.
                    $answer->answer = $properties->answer_editor[$i];
                    $answer->answerformat = FORMAT_MOODLE;
                }
            }
            if (!empty($properties->response_editor[$i]) && is_array($properties->response_editor[$i])) {
                $answer->response = $properties->response_editor[$i]['text'];
                $answer->responseformat = $properties->response_editor[$i]['format'];
            }

            if (isset($answer->answer) && $answer->answer != '') {
                if (isset($properties->jumpto[$i])) {
                    $answer->jumpto = $properties->jumpto[$i];
                }
                if ($this->xfgon->custom && isset($properties->score[$i])) {
                    $answer->score = $properties->score[$i];
                }
                $answer->id = $DB->insert_record("xfgon_answers", $answer);
                if (isset($properties->response_editor[$i])) {
                    $this->save_answers_files($context, $PAGE->course->maxbytes, $answer,
                            $properties->answer_editor[$i], $properties->response_editor[$i]);
                } else {
                    $this->save_answers_files($context, $PAGE->course->maxbytes, $answer,
                            $properties->answer_editor[$i]);
                }
                $answers[$answer->id] = new xfgon_page_answer($answer);
            } else {
                break;
            }
        }

        $this->answers = $answers;
        return $answers;
    }

    /**
     * This method MUST be overridden by all question page types, or page types that
     * wish to score a page.
     *
     * The structure of result should always be the same so it is a good idea when
     * overriding this method on a page type to call
     * <code>
     * $result = parent::check_answer();
     * </code>
     * before modifying it as required.
     *
     * @return stdClass
     */
    public function check_answer() {
        $result = new stdClass;
        $result->answerid        = 0;
        $result->noanswer        = false;
        $result->correctanswer   = false;
        $result->isessayquestion = false;   // use this to turn off review button on essay questions
        $result->response        = '';
        $result->newpageid       = 0;       // stay on the page
        $result->studentanswer   = '';      // use this to store student's answer(s) in order to display it on feedback page
        $result->studentanswerformat = FORMAT_MOODLE;
        $result->userresponse    = null;
        $result->feedback        = '';
        $result->nodefaultresponse  = false; // Flag for redirecting when default feedback is turned off
        $result->inmediatejump = false; // Flag to detect when we should do a jump from the page without further processing.
        return $result;
    }

    /**
     * True if the page uses a custom option
     *
     * Should be override and set to true if the page uses a custom option.
     *
     * @return bool
     */
    public function has_option() {
        return false;
    }

    /**
     * Returns the maximum number of answers for this page given the maximum number
     * of answers permitted by the xfgon.
     *
     * @param int $default
     * @return int
     */
    public function max_answers($default) {
        return $default;
    }

    /**
     * Returns the properties of this xfgon page as an object
     * @return stdClass;
     */
    public function properties() {
        $properties = clone($this->properties);
        if ($this->answers === null) {
            $this->get_answers();
        }
        if (count($this->answers)>0) {
            $count = 0;
            $qtype = $properties->qtype;
            foreach ($this->answers as $answer) {
                $properties->{'answer_editor['.$count.']'} = array('text' => $answer->answer, 'format' => $answer->answerformat);
                if ($qtype != xfgon_PAGE_MATCHING) {
                    $properties->{'response_editor['.$count.']'} = array('text' => $answer->response, 'format' => $answer->responseformat);
                } else {
                    $properties->{'response_editor['.$count.']'} = $answer->response;
                }
                $properties->{'jumpto['.$count.']'} = $answer->jumpto;
                $properties->{'score['.$count.']'} = $answer->score;
                $count++;
            }
        }
        return $properties;
    }

    /**
     * Returns an array of options to display when choosing the jumpto for a page/answer
     * @static
     * @param int $pageid
     * @param xfgon $xfgon
     * @return array
     */
    public static function get_jumptooptions($pageid, xfgon $xfgon) {
        global $DB;
        $jump = array();
        $jump[0] = get_string("thispage", "xfgon");
        $jump[xfgon_NEXTPAGE] = get_string("nextpage", "xfgon");
        $jump[xfgon_PREVIOUSPAGE] = get_string("previouspage", "xfgon");
        $jump[xfgon_EOL] = get_string("endofxfgon", "xfgon");

        if ($pageid == 0) {
            return $jump;
        }

        $pages = $xfgon->load_all_pages();
        if ($pages[$pageid]->qtype == xfgon_PAGE_BRANCHTABLE || $xfgon->is_sub_page_of_type($pageid, array(xfgon_PAGE_BRANCHTABLE), array(xfgon_PAGE_ENDOFBRANCH, xfgon_PAGE_CLUSTER))) {
            $jump[xfgon_UNSEENBRANCHPAGE] = get_string("unseenpageinbranch", "xfgon");
            $jump[xfgon_RANDOMPAGE] = get_string("randompageinbranch", "xfgon");
        }
        if($pages[$pageid]->qtype == xfgon_PAGE_CLUSTER || $xfgon->is_sub_page_of_type($pageid, array(xfgon_PAGE_CLUSTER), array(xfgon_PAGE_ENDOFCLUSTER))) {
            $jump[xfgon_CLUSTERJUMP] = get_string("clusterjump", "xfgon");
        }
        if (!optional_param('firstpage', 0, PARAM_INT)) {
            $apageid = $DB->get_field("xfgon_pages", "id", array("xfgonid" => $xfgon->id, "prevpageid" => 0));
            while (true) {
                if ($apageid) {
                    $title = $DB->get_field("xfgon_pages", "title", array("id" => $apageid));
                    $jump[$apageid] = strip_tags(format_string($title,true));
                    $apageid = $DB->get_field("xfgon_pages", "nextpageid", array("id" => $apageid));
                } else {
                    // last page reached
                    break;
                }
            }
        }
        return $jump;
    }
    /**
     * Returns the contents field for the page properly formatted and with plugin
     * file url's converted
     * @return string
     */
    public function get_contents() {
        global $PAGE;
        if (!empty($this->properties->contents)) {
            if (!isset($this->properties->contentsformat)) {
                $this->properties->contentsformat = FORMAT_HTML;
            }
            $context = context_module::instance($PAGE->cm->id);
            $contents = file_rewrite_pluginfile_urls($this->properties->contents, 'pluginfile.php', $context->id, 'mod_xfgon',
                                                     'page_contents', $this->properties->id);  // Must do this BEFORE format_text()!
            return format_text($contents, $this->properties->contentsformat,
                               array('context' => $context, 'noclean' => true,
                                     'overflowdiv' => true));  // Page edit is marked with XSS, we want all content here.
        } else {
            return '';
        }
    }

    /**
     * Set to true if this page should display in the menu block
     * @return bool
     */
    protected function get_displayinmenublock() {
        return false;
    }

    /**
     * Get the string that describes the options of this page type
     * @return string
     */
    public function option_description_string() {
        return '';
    }

    /**
     * Updates a table with the answers for this page
     * @param html_table $table
     * @return html_table
     */
    public function display_answers(html_table $table) {
        $answers = $this->get_answers();
        $i = 1;
        foreach ($answers as $answer) {
            $cells = array();
            $cells[] = '<label>' . get_string('jump', 'xfgon') . ' ' . $i . '</label>:';
            $cells[] = $this->get_jump_name($answer->jumpto);
            $table->data[] = new html_table_row($cells);
            if ($i === 1){
                $table->data[count($table->data)-1]->cells[0]->style = 'width:20%;';
            }
            $i++;
        }
        return $table;
    }

    /**
     * Determines if this page should be grayed out on the management/report screens
     * @return int 0 or 1
     */
    protected function get_grayout() {
        return 0;
    }

    /**
     * Adds stats for this page to the &pagestats object. This should be defined
     * for all page types that grade
     * @param array $pagestats
     * @param int $tries
     * @return bool
     */
    public function stats(array &$pagestats, $tries) {
        return true;
    }

    /**
     * Formats the answers of this page for a report
     *
     * @param object $answerpage
     * @param object $answerdata
     * @param object $useranswer
     * @param array $pagestats
     * @param int $i Count of first level answers
     * @param int $n Count of second level answers
     * @return object The answer page for this
     */
    public function report_answers($answerpage, $answerdata, $useranswer, $pagestats, &$i, &$n) {
        $answers = $this->get_answers();
        $formattextdefoptions = new stdClass;
        $formattextdefoptions->para = false;  //I'll use it widely in this page
        foreach ($answers as $answer) {
            $data = get_string('jumpsto', 'xfgon', $this->get_jump_name($answer->jumpto));
            $answerdata->answers[] = array($data, "");
            $answerpage->answerdata = $answerdata;
        }
        return $answerpage;
    }

    /**
     * Gets an array of the jumps used by the answers of this page
     *
     * @return array
     */
    public function get_jumps() {
        global $DB;
        $jumps = array();
        $params = array ("xfgonid" => $this->xfgon->id, "pageid" => $this->properties->id);
        if ($answers = $this->get_answers()) {
            foreach ($answers as $answer) {
                $jumps[] = $this->get_jump_name($answer->jumpto);
            }
        } else {
            $jumps[] = $this->get_jump_name($this->properties->nextpageid);
        }
        return $jumps;
    }
    /**
     * Informs whether this page type require manual grading or not
     * @return bool
     */
    public function requires_manual_grading() {
        return false;
    }

    /**
     * A callback method that allows a page to override the next page a user will
     * see during when this page is being completed.
     * @return false|int
     */
    public function override_next_page() {
        return false;
    }

    /**
     * This method is used to determine if this page is a valid page
     *
     * @param array $validpages
     * @param array $pageviews
     * @return int The next page id to check
     */
    public function valid_page_and_view(&$validpages, &$pageviews) {
        $validpages[$this->properties->id] = 1;
        return $this->properties->nextpageid;
    }

    /**
     * Get files from the page area file.
     *
     * @param bool $includedirs whether or not include directories
     * @param int $updatedsince return files updated since this time
     * @return array list of stored_file objects
     * @since  Moodle 3.2
     */
    public function get_files($includedirs = true, $updatedsince = 0) {
        $fs = get_file_storage();
        return $fs->get_area_files($this->xfgon->context->id, 'mod_xfgon', 'page_contents', $this->properties->id,
                                    'itemid, filepath, filename', $includedirs, $updatedsince);
    }
}



/**
 * Class used to represent an answer to a page
 *
 * @property int $id The ID of this answer in the database
 * @property int $xfgonid The ID of the xfgon this answer belongs to
 * @property int $pageid The ID of the page this answer belongs to
 * @property int $jumpto Identifies where the user goes upon completing a page with this answer
 * @property int $grade The grade this answer is worth
 * @property int $score The score this answer will give
 * @property int $flags Used to store options for the answer
 * @property int $timecreated A timestamp of when the answer was created
 * @property int $timemodified A timestamp of when the answer was modified
 * @property string $answer The answer itself
 * @property string $response The response the user sees if selecting this answer
 *
 * @copyright  2009 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class xfgon_page_answer extends xfgon_base {

    /**
     * Loads an page answer from the DB
     *
     * @param int $id
     * @return xfgon_page_answer
     */
    public static function load($id) {
        global $DB;
        // $answer = $DB->get_record("xfgon_answers", array("id" => $id));
        // return new xfgon_page_answer($answer);
    }

    /**
     * Given an object of properties and a page created answer(s) and saves them
     * in the database.
     *
     * @param stdClass $properties
     * @param xfgon_page $page
     * @return array
     */
    public static function create($properties, xfgon_page $page) {
        return $page->create_answers($properties);
    }

    /**
     * Get files from the answer area file.
     *
     * @param bool $includedirs whether or not include directories
     * @param int $updatedsince return files updated since this time
     * @return array list of stored_file objects
     * @since  Moodle 3.2
     */
    public function get_files($includedirs = true, $updatedsince = 0) {

        $xfgon = xfgon::load($this->properties->xfgonid);
        $fs = get_file_storage();
        $answerfiles = $fs->get_area_files($xfgon->context->id, 'mod_xfgon', 'page_answers', $this->properties->id,
                                            'itemid, filepath, filename', $includedirs, $updatedsince);
        $responsefiles = $fs->get_area_files($xfgon->context->id, 'mod_xfgon', 'page_responses', $this->properties->id,
                                            'itemid, filepath, filename', $includedirs, $updatedsince);
        return array_merge($answerfiles, $responsefiles);
    }

}

/**
 * A management class for page types
 *
 * This class is responsible for managing the different pages. A manager object can
 * be retrieved by calling the following line of code:
 * <code>
 * $manager  = xfgon_page_type_manager::get($xfgon);
 * </code>
 * The first time the page type manager is retrieved the it includes all of the
 * different page types located in mod/xfgon/pagetypes.
 *
 * @copyright  2009 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class xfgon_page_type_manager {

    /**
     * An array of different page type classes
     * @var array
     */
    protected $types = array();

    /**
     * Retrieves the xfgon page type manager object
     *
     * If the object hasn't yet been created it is created here.
     *
     * @staticvar xfgon_page_type_manager $pagetypemanager
     * @param xfgon $xfgon
     * @return xfgon_page_type_manager
     */
    public static function get(xfgon $xfgon) {
        static $pagetypemanager;
        if (!($pagetypemanager instanceof xfgon_page_type_manager)) {
            $pagetypemanager = new xfgon_page_type_manager();
            // $pagetypemanager->load_xfgon_types($xfgon);
        }
        return $pagetypemanager;
    }

    /**
     * Finds and loads all xfgon page types in mod/xfgon/pagetypes
     *
     * @param xfgon $xfgon
     */
    public function load_xfgon_types(xfgon $xfgon) {
        global $CFG;
        $basedir = $CFG->dirroot.'/mod/xfgon/pagetypes/';
        $dir = dir($basedir);
        while (false !== ($entry = $dir->read())) {
            if (strpos($entry, '.')===0 || !preg_match('#^[a-zA-Z]+\.php#i', $entry)) {
                continue;
            }
            require_once($basedir.$entry);
            $class = 'xfgon_page_type_'.strtok($entry,'.');
            if (class_exists($class)) {
                $pagetype = new $class(new stdClass, $xfgon);
                $this->types[$pagetype->typeid] = $pagetype;
            }
        }

    }

    /**
     * Returns an array of strings to describe the loaded page types
     *
     * @param int $type Can be used to return JUST the string for the requested type
     * @return array
     */
    public function get_page_type_strings($type=null, $special=true) {
        $types = array();
        foreach ($this->types as $pagetype) {
            if (($type===null || $pagetype->type===$type) && ($special===true || $pagetype->is_standard())) {
                $types[$pagetype->typeid] = $pagetype->typestring;
            }
        }
        return $types;
    }

    /**
     * Returns the basic string used to identify a page type provided with an id
     *
     * This string can be used to instantiate or identify the page type class.
     * If the page type id is unknown then 'unknown' is returned
     *
     * @param int $id
     * @return string
     */
    public function get_page_type_idstring($id) {
        foreach ($this->types as $pagetype) {
            if ((int)$pagetype->typeid === (int)$id) {
                return $pagetype->idstring;
            }
        }
        return 'unknown';
    }

    /**
     * Loads a page for the provided xfgon given it's id
     *
     * This function loads a page from the xfgon when given both the xfgon it belongs
     * to as well as the page's id.
     * If the page doesn't exist an error is thrown
     *
     * @param int $pageid The id of the page to load
     * @param xfgon $xfgon The xfgon the page belongs to
     * @return xfgon_page A class that extends xfgon_page
     */
    public function load_page($pageid, xfgon $xfgon) {
        global $DB;
        $pagetype = get_class($this->types[$page->qtype]);
        $page = new $pagetype($page, $xfgon);
        return $page;
    }

    /**
     * This function detects errors in the ordering between 2 pages and updates the page records.
     *
     * @param stdClass $page1 Either the first of 2 pages or null if the $page2 param is the first in the list.
     * @param stdClass $page1 Either the second of 2 pages or null if the $page1 param is the last in the list.
     */
    protected function check_page_order($page1, $page2) {
        global $DB;
        // To be improved...
    }

    /**
     * This function loads ALL pages that belong to the xfgon.
     *
     * @param xfgon $xfgon
     * @return array An array of xfgon_page_type_*
     */
    public function load_all_pages(xfgon $xfgon) {
        global $DB;

        $orderedpages = array();
        return $orderedpages;
    }

    /**
     * Fetches an mform that can be used to create/edit an page
     *
     * @param int $type The id for the page type
     * @param array $arguments Any arguments to pass to the mform
     * @return xfgon_add_page_form_base
     */
    public function get_page_form($type, $arguments) {
        $class = 'xfgon_add_page_form_'.$this->get_page_type_idstring($type);
        if (!class_exists($class) || get_parent_class($class)!=='xfgon_add_page_form_base') {
            debugging('Lesson page type unknown class requested '.$class, DEBUG_DEVELOPER);
            $class = 'xfgon_add_page_form_selection';
        } else if ($class === 'xfgon_add_page_form_unknown') {
            $class = 'xfgon_add_page_form_selection';
        }
        return new $class(null, $arguments);
    }

    /**
     * Returns an array of links to use as add page links
     * @param int $previd The id of the previous page
     * @return array
     */
    public function get_add_page_type_links($previd) {
        global $OUTPUT;

        $links = array();

        foreach ($this->types as $key=>$type) {
            if ($link = $type->add_page_link($previd)) {
                $links[$key] = $link;
            }
        }

        return $links;
    }
}


function get_res_awesomeicon($mime_type) {
  // List of official MIME Types: http://www.iana.org/assignments/media-types/media-types.xhtml
  $icon_classes = array(
      // Media
      'application/image'=> 'fa-file-image-o',
      'application/audio'=>'fa-file-audio-o',
      'application/video'=>'fa-file-video-o',
      'video/mp4'=>'fa-file-video-o',
      // Documents
      'application/pdf'=> 'fa-file-pdf-o',
      'application/msword'=> 'fa-file-word-o',
      'application/vnd.ms-word'=> 'fa-file-word-o',
      'application/vnd.oasis.opendocument.text'=> 'fa-file-word-o',
      'application/vnd.openxmlformats-officedocument.wordprocessingml'=> 'fa-file-word-o',
      'application/vnd.ms-excel'=> 'fa-file-excel-o',
      'application/vnd.openxmlformats-officedocument.spreadsheetml'=> 'fa-file-excel-o',
      'application/vnd.oasis.opendocument.spreadsheet'=> 'fa-file-excel-o',
      'application/vnd.ms-powerpoint'=> 'fa-file-powerpoint-o',
      'application/vnd.openxmlformats-officedocument.presentationml'=> 'fa-file-powerpoint-o',
      'application/vnd.oasis.opendocument.presentation'=> 'fa-file-powerpoint-o',
      'application/plain'=> 'fa-file-text-o',
      'application/html'=> 'fa-file-code-o',
      'application/json'=> 'fa-file-code-o',
      'text/plain'=> 'fa-file-text-o',
      'text/html'=> 'fa-file-code-o',
      'text/json'=> 'fa-file-code-o',
      // Archives
      'application/gzip'=> 'fa-file-archive-o',
      'application/zip'=> 'fa-file-archive-o',
      // Code
      'application/code' => 'fa fa-file-code-o'
  );
  foreach ($icon_classes as $text => $icon) {
    if (strpos($text, $mime_type) !== false) {
      return $icon;
    }
  }
  return 'fa-file-o';
}



function get_plst_items($pst5lnftch){
        global $DB;

        $plst = new stdClass();
        if (
            isset($pst5lnftch['pst5lnftchfmmbz'])
            and $pst5lnftch['pst5lnftchfmmbz']!=null
            and $pst5lnftch['pst5lnftchmd'] == "pst5lnftchfmmbz"
        )
        {
            try {

                $plst = get_plst_items_fmmbz($pst5lnftch['pst5lnftchfmmbz']);

            } catch (\Exception $e) {
                // print_r($e, $plst);
            }


        }
        elseif (
            isset($pst5lnftch['pst5lnftchfmurl'])
            and $pst5lnftch['pst5lnftchfmurl']!=null
            and $pst5lnftch['pst5lnftchmd'] == "pst5lnftchfmurl"
        )
        {

            $plst = get_plst_items_fmurl($pst5lnftch['pst5lnftchfmurl']);

        }else{

            $plst->plst = new stdClass();
            $plst->plst->playlist_items = array();
            $plst->fetch = "failure";
            $plst->fetchdtls = "no playlist needed to be fetched";
        }

        return $plst;
  }

  function get_plst_items_fmurl($plsturl){

        $x5lnplstendpoint = 'https://x5learn.org/api/v1/playlist/%s/json';
        $url = sprintf($x5lnplstendpoint, explode("q=pl:", $plsturl)[1]);
        $ftchfmurlrt = send_get_request($url);

        $plstenrichurl = 'https://wp3.x5gon.org/others/moodle/playlist2mbz';
        $ftchfmurlrt->playlist_format = "json";
        $enrichrslt = send_post_request($plstenrichurl, $ftchfmurlrt);
        $enrichplst = new stdClass();$enrichplst->plst = $enrichrslt;

        if(property_exists($enrichplst->plst, "playlist_general_infos")){
            $enrichplst->fetch = "success";
            $enrichplst->fetchdtls = "";
        }else {
            $enrichplst->fetch = "failure";
            $enrichplst->fetchdtls = "Playlist not found check if it's the suitable url !";
        }
        return $enrichplst;
  }

  function get_plst_items_fmmbz($itemid){

         global $USER, $CFG;

         $plst_sum = new stdClass();
         $plst_sum->plst = new stdClass();
         $plst_sum->fetch = "failure";

         $context = context_user::instance($USER->id);
         $filename = null;

         $fs = get_file_storage();
         $sf = $fs->get_area_files($context->id, "user", "draft", $itemid, $includedirs = false);
         foreach ($sf as $f) {
             // $f is an instance of stored_file
             if($f->get_filename() != "."){
                $filename = $f->get_filename();

             }
         }

         if ($filename != null){
             $ud_file = $fs->get_file($context->id, "user", "draft", $itemid, "/", $filename);
             $packer = get_file_packer('application/vnd.moodle.backup');
             $ud_file_cthash = $ud_file->get_contenthash();
             $ud_file_pth = str_split($ud_file_cthash, 2);
             $plstmbz_content = $packer->list_files($CFG->dataroot."/filedir/".$ud_file_pth[0]."/".$ud_file_pth[1]."/".$ud_file_cthash);

              $mbz = new ZipArchive;
              if ($mbz->open($CFG->dataroot."/filedir/".$ud_file_pth[0]."/".$ud_file_pth[1]."/".$ud_file_cthash) === TRUE) {
                  if($mbz->locateName('plst.json') !== false){
                      $plst_sum->plst = json_decode($mbz->getFromName('plst.json'));
                      $plst_sum->fetch = "success";
                      $plst_sum->fetchdtls = "";
                  }else{
                      $plst_sum->fetchdtls = "playlist summary file unfound: mbz not compatible, try to dwonload again your mbz";
                  }
                  $mbz->close();
              } else {
                  $plst_sum->fetchdtls = "unseccessful upacking mbz file operation.";
              }

          } else{

              $plst_sum->fetchdtls = "No mbz file is specified !";

          }
         return $plst_sum;
  }
