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


$string['accesscontrol'] = 'Access control';
$string['actionaftercorrectanswer'] = 'Action after correct answer';
$string['actionaftercorrectanswer_help'] = 'After answering a question correctly, there are 3 options for the following page:

* Normal - Follow xfgon path
* Show an unseen page - Pages are shown in a random order with no page shown twice
* Show an unanswered page - Pages are shown in a random order, with pages containing unanswered questions shown again';
$string['actions'] = 'Actions';
$string['activitylink'] = 'Link to next activity';
$string['activitylink_help'] = 'To provide a link at the end of the xfgon to another activity in the course, select the activity from the drop-down menu.';
$string['activitylinkname'] = 'Go to {$a}';
$string['activityoverview'] = 'You have xfgons that are due';

$string['and'] = 'AND';
$string['anchortitle'] = 'Start of main content';
$string['answer'] = 'Answer';
$string['answeredcorrectly'] = 'answered correctly.';

$string['available'] = 'Available from';
$string['averagescore'] = 'Average score';
$string['averagetime'] = 'Average time';
$string['branch'] = 'Content';
$string['branchtable'] = 'Content';
$string['cancel'] = 'Cancel';

$string['cannotfinduser'] = 'Error: could not find users';
$string['canretake'] = '{$a} can re-take';
$string['casesensitive'] = 'Use regular expressions';
$string['casesensitive_help'] = 'Tick the checkbox to use regular expressions for analysing responses.';
$string['classstats'] = 'Class statistics';
$string['clicktodownload'] = 'Click on the following link to download the file.';
$string['closebeforeopen'] = 'Could not update the xfgon. You have specified a close date before the open date.';

$string['comments'] = 'Your comments';
$string['completed'] = 'Completed';
$string['completederror'] = 'Complete the xfgon';
$string['completethefollowingconditions'] = 'You must complete the following condition(s) in <b>{$a}</b> xfgon before you can proceed.';
$string['completionendreached'] = 'Require end reached';
$string['completionendreached_desc'] = 'Student must reach the end of xfgon page to complete this activity';
$string['completiontimespent'] = 'Student must do this activity at least for';
$string['completiontimespentdesc'] = 'Student must do this activity for at least {$a}';
$string['completiontimespentgroup'] = 'Require time spent';
$string['conditionsfordependency'] = 'Condition(s) for the dependency';
$string['configintro'] = 'The values set here define the default values that are used in the settings form when creating a new xfgon activity. Settings specified as advanced are only shown when the \'Show more...\' link is clicked.';
$string['configmaxanswers'] = 'Default maximum number of answers per page';
$string['configmediaclose'] = 'Displays a close button as part of the popup generated for a linked media file';
$string['configmediaheight'] = 'Sets the height of the popup displayed for a linked media file';
$string['configmediawidth'] = 'Sets the width of the popup displayed for a linked media file';
$string['configpassword_desc'] = 'Whether a password is required in order to access the xfgon.';
$string['configslideshowbgcolor'] = 'Background colour to for the slideshow if it is enabled';
$string['configslideshowheight'] = 'Sets the height of the slideshow if it is enabled';
$string['configslideshowwidth'] = 'Sets the width of the slideshow if it is enabled';
$string['configtimelimit_desc'] = 'If a time limit is set, a warning is displayed at the beginning of the xfgon and there is a countdown timer. If set to zero, then there is no time limit.';
$string['confirmdelete'] = 'Delete page';
$string['confirmdeletionofthispage'] = 'Confirm deletion of this page';
$string['congratulations'] = 'Congratulations - end of xfgon reached';
$string['continue'] = 'Continue';
$string['continuetoanswer'] = 'Continue to change answers.';
$string['continuetonextpage'] = 'Continue to next page.';
$string['correctanswerjump'] = 'Correct answer jump';
$string['correctanswerscore'] = 'Correct answer score';
$string['correctresponse'] = 'Correct response';
$string['createaquestionpage'] = 'Create a question page';
$string['credit'] = 'Credit';
$string['customscoring'] = 'Custom scoring';
$string['customscoring_help'] = 'If enabled, then each answer may be given a numerical point value (positive or negative).';
$string['deadline'] = 'Deadline';
$string['defaultessayresponse'] = 'Your essay will be graded by your teacher.';

$string['deleting'] = 'Deleting';

$string['disabled'] = 'Disabled';
$string['displaydefaultfeedback'] = 'Use default feedback';
$string['displaydefaultfeedback_help'] = 'If enabled, when a response is not found for a particular question, the default response of "That\'s the correct answer" or "That\'s the wrong answer" will be shown.';
$string['displayinleftmenu'] = 'Display in menu?';
$string['displayleftif'] = 'Minimum grade to display menu';
$string['displayleftif_help'] = 'This setting determines whether a student must obtain a certain grade before viewing the xfgon menu. This forces the student to go through the entire xfgon on their first attempt, then after obtaining the required grade they can use the menu for review.';
$string['displayleftmenu'] = 'Display menu';
$string['displayleftmenu_help'] = 'If enabled, a menu allowing users to navigate through the list of pages is displayed.';
$string['displayofgrade'] = 'Display of grade (for students only)';
$string['displayreview'] = 'Provide option to try a question again';
$string['displayreview_help'] = 'If enabled, when a question is answered incorrectly, the student is given the option to try it again for no point credit, or continue with the xfgon.';
$string['displayscorewithessays'] = '<p>You earned {$a->score} out of {$a->tempmaxgrade} for the automatically graded questions.</p>
<p>Your {$a->essayquestions} essay question(s) will be graded and added into your final score at a later date.</p>
<p>Your current grade without the essay question(s) is {$a->score} out of {$a->grade}.</p>';
$string['displayscorewithoutessays'] = 'Your score is {$a->score} (out of {$a->grade}).';


$string['edit'] = 'Edit';

$string['email'] = 'Email';

$string['enabled'] = 'Enabled';

$string['endofxfgon'] = 'End of xfgon';

$string['eventxfgonended'] = 'xfgon ended';
$string['eventxfgonrestarted'] = 'xfgon restarted';
$string['eventxfgonresumed'] = 'xfgon resumed';
$string['eventxfgonstarted'] = 'xfgon started';

$string['false'] = 'False';
$string['fileformat'] = 'File format';

$string['finish'] = 'Finish';
$string['full'] = 'Expanded';
$string['general'] = 'General';

$string['grade'] = 'Grade';
$string['gradebetterthan'] = 'Grade better than (&#37;)';
$string['gradebetterthanerror'] = 'Earn a grade better than {$a} percent';
$string['graded'] = 'Graded';

$string['here'] = 'here';

$string['checknavigation'] = 'Check navigation';
$string['checkquestion'] = 'Check question';
$string['invalidfile'] = 'Invalid file';
$string['invalidid'] = 'No course module ID or xfgon ID were passed';
$string['invalidxfgonid'] = 'xfgon ID was incorrect';

$string['xfgon:addinstance'] = 'Add a new xfgon';

$string['xfgonclosed'] = 'This xfgon closed on {$a}.';
$string['xfgoncloses'] = 'xfgon closes';
$string['xfgoneventcloses'] = '{$a} closes';
$string['xfgoncloseson'] = 'xfgon closes on {$a}';
$string['xfgon:edit'] = 'Edit a xfgon activity';
$string['xfgonformating'] = 'xfgon formatting';
$string['xfgon:manage'] = 'Manage a xfgon activity';

$string['xfgon:view'] = 'View xfgon activity';
$string['xfgon:viewreports'] = 'View xfgon reports';
$string['xfgonname'] = 'xfgon: {$a}';
$string['xfgonmenu'] = 'xfgon menu';

$string['xfgonopens'] = 'xfgon opens';
$string['xfgoneventopens'] = '{$a} opens';
$string['xfgonpagelinkingbroken'] = 'First page not found.  xfgon page linking must be broken.  Please contact an admin.';
$string['xfgonstats'] = 'xfgon statistics';
$string['linkedmedia'] = 'Linked media';
$string['loginfail'] = 'Login failed, please try again...';
$string['mediaclose'] = 'Show close button';
$string['mediafile'] = 'Linked media';
$string['mediafile_help'] = 'A media file may be uploaded for use in the xfgon. A \'Click here to view\' link will then be displayed in a block called \'Linked media\' on each page of the xfgon.';
$string['mediafilepopup'] = 'Click here to view';
$string['mediaheight'] = 'Popup window height';
$string['mediawidth'] = 'Popup window width';
$string['messageprovider:graded_essay'] = 'xfgon essay graded notification';
$string['minimumnumberofquestions'] = 'Minimum number of questions';
$string['minimumnumberofquestions_help'] = 'This setting specifies the minimum number of questions that will be used to calculate a grade for the activity.';
$string['missingname'] = 'Please enter a nickname';
$string['xfgon:grade'] = 'Grade xfgon essay questions';
$string['xfgon:manageoverrides'] = 'Manage xfgon overrides';


$string['new'] = 'new';
$string['nextpage'] = 'Next page';
$string['noanswer'] = 'One or more questions have no answer given.  Please go back and submit an answer.';
$string['noattemptrecordsfound'] = 'No attempt records found: no grade given';
$string['nobranchtablefound'] = 'No content page found';
$string['noclose'] = 'No close date';
$string['nocommentyet'] = 'No comment yet.';
$string['nocoursemods'] = 'No activities found';

$string['nodeadline'] = 'No deadline';
$string['noessayquestionsfound'] = 'No essay questions found in this xfgon.';
$string['nohighscores'] = 'No high scores';
$string['noxfgonattempts'] = 'No attempts have been made on this xfgon.';
$string['noxfgonattemptsgroup'] = 'No attempts have been made by {$a} group members on this xfgon.';
$string['none'] = 'None';
$string['nooneansweredcorrectly'] = 'No one answered correctly.';
$string['nooneansweredthisquestion'] = 'No one answered this question.';
$string['nooneenteredthis'] = 'No one entered this.';
$string['noonehasanswered'] = 'No one has answered an essay question yet.';
$string['noonehasansweredgroup'] = 'No one in {$a} has answered an essay question yet.';
$string['noonecheckedthis'] = 'No one checked this.';
$string['noopen'] = 'No open date';
$string['nooverridedata'] = 'You must override at least one of the xfgon settings.';
$string['noretake'] = 'You are not allowed to retake this xfgon.';
$string['normal'] = 'Normal - follow xfgon path';
$string['notcompleted'] = 'Not completed';
$string['notyetcompleted'] = 'xfgon has been started, but not yet completed';
$string['notdefined'] = 'Not defined';
$string['notenoughsubquestions'] = 'Not enough sub-questions have been defined!';
$string['notenoughtimespent'] = 'You completed this xfgon in {$a->timespent}, which is less than the required time of {$a->timerequired}. You might need to attempt the xfgon again.';
$string['notgraded'] = 'Not graded';
$string['notitle'] = 'No title';
$string['numerical'] = 'Numerical';
$string['offlinedatamessage'] = 'You have worked on this attempt using a mobile device. Data was last saved to this site {$a} ago. Please check that you do not have any unsaved work.';
$string['ongoing'] = 'Display ongoing score';
$string['ongoing_help'] = 'If enabled, each page will display the student\'s current points earned out of the total possible thus far.';
$string['ongoingcustom'] = 'You have earned {$a->score} point(s) out of {$a->currenthigh} point(s) thus far.';
$string['ongoingnormal'] = 'You have answered {$a->correct} correctly out of {$a->viewed} attempts.';
$string['onpostperpage'] = 'Only one posting per grade';
$string['openafterclose'] = 'You have specified an open date after the close date';
$string['options'] = 'Options';
$string['or'] = 'OR';
$string['ordered'] = 'Ordered';
$string['other'] = 'Other';
$string['outof'] = 'Out of {$a}';

$string['overview'] = 'Overview';
$string['overview_help'] = 'A xfgon is made up of a number of pages and optionally content pages. A page contains some content and usually ends with a question. Associated with each answer to the question is a jump. The jump can be relative, such as this page or next page, or absolute, specifying any one of the pages in the xfgon. A content page is a page containing a set of links to other pages in the xfgon, for example a Table of Contents.';



$string['processerror'] = 'Error occurred during processing!';
$string['progressbar'] = 'Progress bar';
$string['progressbar_help'] = 'If enabled, a bar is displayed at the bottom of xfgon pages showing approximate percentage of completion.';
$string['progresscompleted'] = 'You have completed {$a}% of the xfgon';
$string['progressbarteacherwarning'] = 'Progress bar does not display for {$a}';
$string['progressbarteacherwarning2'] = 'You will not see the progress bar because you can edit this xfgon';

$string['report'] = 'Report';
$string['reports'] = 'Reports';
$string['response'] = 'Response';
$string['retakesallowed'] = 'Re-takes allowed';
$string['retakesallowed_help'] = 'If enabled, students can attempt the xfgon more than once.';
$string['returnto'] = 'Return to {$a}';
$string['returntocourse'] = 'Return to the course';
$string['reverttodefaults'] = 'Revert to xfgon defaults';
$string['review'] = 'Review';
$string['reviewxfgon'] = 'Review xfgon';
$string['reviewquestionback'] = 'Yes, I\'d like to try again';
$string['reviewquestioncontinue'] = 'No, I just want to go on to the next question';
$string['sanitycheckfailed'] = 'Sanity check failed: This attempt has been deleted';
$string['save'] = 'Save';
$string['savechanges'] = 'Save changes';
$string['savechangesandeol'] = 'Save all changes and go to the end of the xfgon.';
$string['saveoverrideandstay'] = 'Save and enter another override';
$string['savepage'] = 'Save page';
$string['score'] = 'Score';
$string['score_help'] = 'Score is only used when custom scoring is enabled. Each answer can then be given a numerical point value (positive or negative).';
$string['scores'] = 'Scores';
$string['search:activity'] = 'xfgon - activity information';
$string['secondpluswrong'] = 'Not quite.  Would you like to try again?';
$string['selectaqtype'] = 'Select a question type';
$string['sent'] = 'Sent';
$string['shortanswer'] = 'Short answer';
$string['showanunansweredpage'] = 'Show an unanswered page';
$string['showanunseenpage'] = 'Show an unseen page';
$string['singleanswer'] = 'Single answer';
$string['skip'] = 'Skip navigation';
$string['slideshow'] = 'Slideshow';
$string['slideshow_help'] = 'If enabled, the xfgon is displayed as a slideshow, with a fixed width and height.';
$string['slideshowbgcolor'] = 'Slideshow background colour';
$string['slideshowheight'] = 'Slideshow height';
$string['slideshowwidth'] = 'Slideshow width';
$string['startxfgon'] = 'Start xfgon';
$string['studentattemptxfgon'] = '{$a->lastname}, {$a->firstname}\'s attempt number {$a->attempt}';
$string['studentname'] = '{$a} Name';
$string['studentoneminwarning'] = 'Warning: You have 1 minute or less to finish the xfgon.';
$string['studentoutoftimeforreview'] = 'Attention: You ran out of time for reviewing this xfgon';
$string['studentresponse'] = '{$a}\'s response';
$string['submit'] = 'Submit';
$string['submitname'] = 'Submit name';
$string['teacherjumpwarning'] = 'An {$a->cluster} jump or an {$a->unseen} jump is being used in this xfgon.  The next page jump will be used instead.  Login as a student to test these jumps.';
$string['teacherongoingwarning'] = 'Ongoing score is only displayed for student.  Login as a student to test ongoing score';
$string['teachertimerwarning'] = 'Timer only works for students.  Test the timer by logging in as a student.';
$string['thatsthecorrectanswer'] = 'That\'s the correct answer';
$string['thatsthewronganswer'] = 'That\'s the wrong answer';
$string['thefollowingpagesjumptothispage'] = 'The following pages jump to this page';

$string['true'] = 'True';
$string['truefalse'] = 'True/false';
$string['unabledtosavefile'] = 'The file you uploaded could not be saved';
$string['viewreports'] = 'View {$a->attempts} completed {$a->student} attempts';
$string['viewreports2'] = 'View {$a} completed attempts';
$string['warning'] = 'Warning';
$string['welldone'] = 'Well done!';
$string['whatdofirst'] = 'What would you like to do first?';




$string['externalurl'] = 'External URL';
$string['framesize'] = 'Frame height';
$string['invalidstoredurl'] = 'Cannot display this resource, URL is invalid.';
$string['chooseavariable'] = 'Choose a variable...';
$string['indicator:cognitivedepth'] = 'URL cognitive';
$string['indicator:cognitivedepth_help'] = 'This indicator is based on the cognitive depth reached by the student in a URL resource.';
$string['indicator:socialbreadth'] = 'URL social';
$string['indicator:socialbreadth_help'] = 'This indicator is based on the social breadth reached by the student in a URL resource.';
$string['invalidurl'] = 'Entered URL is invalid';






// General strings
$string['modulename'] = 'X5Moodle';
$string['pluginadministration'] = 'URL module administration';
$string['modulenameplural'] = 'xfgons';
$string['modulename_help'] = 'The X5Moodle module enables a teacher to use X5GON tools built based on artificial intelligence algorithms and open educative resources (OERs), as a support materials in a first place.
                              In a second place, teacher can follow and see clearly how is going the overall of his students activities. It\'s important to mention that the AI tools
                              used here are comibined with the global usage of the OERs inside the course to ameliorate the proposed resources.
                              For the students, it\'s a funny and in some manner a collaborative experience using AI tools (search engine, recommendation system...)
                              since they can see some usage indicators about the proposed resources which can be a good guidelines inside the course activity (more details on <a target="_blank" href="https://platform.x5gon.org/products/moodle">X5GON website</a>.';
$string['modulename_link'] = 'https://platform.x5gon.org/products/moodle';
$string['pluginname'] = 'X5Moodle';



// Activity part settings
$string['discovery'] = 'X5 Discovery';
$string['recommend'] = 'X5 Recommend';
$string['playlist'] = 'X5 Playlist';
$string['content'] = 'Content';
$string['enablex5discovery'] = 'Enable X5 Discovery';
$string['x5discinit'] = 'Initilize your activity';
$string['enablex5recommend'] = 'Enable X5 Recommend';
$string['enablex5playlist'] = 'Enable X5 Playlist';
$string['pst5lngp'] = 'Upload your playlist';
$string['pst5lnurl'] = 'Playlist url';
$string['pst5lnftch'] = 'From  url';
$string['pst5lnftchfmmbz'] = 'From mbz';
$string['pst5lncrte'] = "Don't have one yet ?";
$string['plstln5mbzatmt'] = "Playlist mbz";
$string['enablex5discovery_help'] = 'Enable X5 Discovery';
$string['enablex5recommend_help'] = 'Enable X5 Recommend';
$string['enablex5playlist_help'] = 'Enable X5 Playlist';
// $string['x5discinit_help'] = 'Type the initial search strings (separated by a commas) that will be the first guidelines for learners. Such as: "Machine learning, Intelligent machines, Bayesian networks..."';
$string['x5discinit_help'] = 'Type the initial terms (separated by a commas) that will be the first guidelines for learners as it will be used to initilize X5-Discovery and X5-Recommend. Such as: "Machine learning, Intelligent machines, Bayesian networks..."';
$string['pst5lngp_help'] = 'Point to your playlist: either enter the "X5learn playlist url" or upload your "X5learn plalist mbz file".';
$string['pstit'] = 'Playlist Item';
$string['pstitadd'] = 'Add Item';
$string['pstitdel'] = 'Remove Item';
$string['previewdiscovery'] = 'X5 Discovery search engine';
$string['previewrecommend'] = 'X5 Recommendation system';
$string['previewplaylist'] = 'X5 Playlist set';


// Localplugin part:
// general plugin settings
$string['enabled'] = 'Enabled';
$string['enabled_desc'] = 'Integrate and activate X5GON project functionalities with respecting OER concepts (<b><a target="_blank" href="https://platform.x5gon.org/products/moodle">About</a></b>, <b><a target="_blank" href="https://gitlab.univ-nantes.fr/x5gon/x5moodle">Gitlab</a></b>, <b><a target="_blank" href="https://github.com/X5GON/x5moodle">Github</a></b>)';
//provider token
$string['providertoken'] = 'Provider Token';
$string['providertoken_desc'] = "Token generated from registering your repository on <a target=\"_blank\" href=\"https://platform.x5gon.org/join\">platform.x5gon.org/join</a>";
//category settings
$string['oercategories'] = 'OER Categories';
$string['oercategories_desc'] = 'Allowed categories to be considered as OER repositories (categories ids separated by commas)';
//course settings
$string['courseallowhidden'] = 'Allow "hidden courses"';
$string['courseallowhidden_desc'] = 'Allow "hidden courses" to be considerd as OER Course';
$string['courseallowtypeswithpass'] = 'Allow "enrolment types with passwords"';
$string['courseallowtypeswithpass_desc'] = 'Allow courses having "enrolment types with passwords" to be considered as OER Course';
$string['courseallowedenroltypes'] = 'Allowed enrolment types';
$string['courseallowedenroltypes_desc'] = 'Allowed enrolment types considered as OER Course (types separated with commas)';
//course modules settings
$string['moduleconsideredmoduletypes'] = 'Allowed module types';
$string['moduleconsideredmoduletypes_desc'] = 'Allowed module types to be treated (types separated with commas)';
$string['moduleallowhidden'] = 'Allow "hidden modules"';
$string['moduleallowhidden_desc'] = 'Allow "hidden modules" to be considered as OER modules';
$string['moduleignoreavailrestric'] = 'Ignore availability restrictions';
$string['moduleignoreavailrestric_desc'] = 'Ignore modules availability(access) restrictions';
//module files settings
$string['fileallowedlicenses'] = 'Allowed file licenses';
$string['fileallowedlicenses_desc'] = 'Allowed file licenses to be considered as OER files (license short names separated with commas)';

// Gui blocks
// Search block:
$string['x5gonapi'] = 'X5GON Search';
$string['x5gonapi:addinstance'] = 'Add a new X5GON Search tab';
$string['x5gonapi:myaddinstance'] = 'Add a new X5GON Search tab to the my moodle course page';
