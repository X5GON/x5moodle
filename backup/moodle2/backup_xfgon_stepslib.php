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

class backup_xfgon_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // The xfgon table
        // This table contains all of the goodness for the xfgon module, quite
        // alot goes into it but nothing relational other than course when will
        // need to be corrected upon restore.
        $xfgon = new backup_nested_element('xfgon', array('id'), array(
            'course', 'name', 'intro', 'introformat', 'practice', 'modattempts',
            'usepassword', 'password',
            'dependency', 'conditions', 'grade', 'custom', 'ongoing', 'usemaxgrade',
            'maxanswers', 'maxattempts', 'review', 'nextpagedefault', 'feedback',
            'minquestions', 'maxpages', 'timelimit', 'retake', 'activitylink',
            'mediafile', 'mediaheight', 'mediawidth', 'mediaclose', 'slideshow',
            'width', 'height', 'bgcolor', 'displayleft', 'displayleftif', 'progressbar',
            'available', 'deadline', 'timemodified',
            'completionendreached', 'completiontimespent', 'allowofflineattempts',
            'contentsettings'
        ));

        // The xfgon_pages table
        // Grouped within a `pages` element, important to note that page is relational
        // to the xfgon, and also to the previous/next page in the series.
        // Upon restore prevpageid and nextpageid will need to be corrected.
        $pages = new backup_nested_element('pages');
        $page = new backup_nested_element('page', array('id'), array(
            'prevpageid','nextpageid','qtype','qoption','layout',
            'display','timecreated','timemodified','title','contents',
            'contentsformat'
        ));

        // The xfgon_answers table
        // Grouped within an answers `element`, the xfgon_answers table relates
        // to the page and xfgon with `pageid` and `xfgonid` that will both need
        // to be corrected during restore.
        $answers = new backup_nested_element('answers');
        $answer = new backup_nested_element('answer', array('id'), array(
            'jumpto','grade','score','flags','timecreated','timemodified','answer_text',
            'response', 'answerformat', 'responseformat'
        ));
        // Tell the answer element about the answer_text elements mapping to the answer
        // database field.
        $answer->set_source_alias('answer', 'answer_text');

        // The xfgon_attempts table
        // Grouped by an `attempts` element this is relational to the page, xfgon,
        // and user.
        $attempts = new backup_nested_element('attempts');
        $attempt = new backup_nested_element('attempt', array('id'), array(
            'userid','retry','correct','useranswer','timeseen'
        ));

        // The xfgon_branch table
        // Grouped by a `branch` element this is relational to the page, xfgon,
        // and user.
        $branches = new backup_nested_element('branches');
        $branch = new backup_nested_element('branch', array('id'), array(
             'userid', 'retry', 'flag', 'timeseen', 'nextpageid'
        ));

        // The xfgon_grades table
        // Grouped by a grades element this is relational to the xfgon and user.
        $grades = new backup_nested_element('grades');
        $grade = new backup_nested_element('grade', array('id'), array(
            'userid','grade','late','completed'
        ));

        // The xfgon_timer table
        // Grouped by a `timers` element this is relational to the xfgon and user.
        $timers = new backup_nested_element('timers');
        $timer = new backup_nested_element('timer', array('id'), array(
            'userid', 'starttime', 'xfgontime', 'completed', 'timemodifiedoffline'
        ));

        $overrides = new backup_nested_element('overrides');
        $override = new backup_nested_element('override', array('id'), array(
            'groupid', 'userid', 'available', 'deadline', 'timelimit',
            'review', 'maxattempts', 'retake', 'password'));

        // Now that we have all of the elements created we've got to put them
        // together correctly.
        $xfgon->add_child($pages);
        $pages->add_child($page);
        $page->add_child($answers);
        $answers->add_child($answer);
        $answer->add_child($attempts);
        $attempts->add_child($attempt);
        $page->add_child($branches);
        $branches->add_child($branch);
        $xfgon->add_child($grades);
        $grades->add_child($grade);
        $xfgon->add_child($timers);
        $timers->add_child($timer);
        $xfgon->add_child($overrides);
        $overrides->add_child($override);

        // Set the source table for the elements that aren't reliant on the user
        // at this point (xfgon, xfgon_pages, xfgon_answers)
        $xfgon->set_source_table('xfgon', array('id' => backup::VAR_ACTIVITYID));

        // Annotate the file areas in user by the xfgon module.
        $xfgon->annotate_files('mod_xfgon', 'intro', null);
        $xfgon->annotate_files('mod_xfgon', 'mediafile', null);

        // Prepare and return the structure we have just created for the xfgon module.
        return $this->prepare_activity_structure($xfgon);
    }
}
