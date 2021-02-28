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


namespace mod_xfgon;

defined('MOODLE_INTERNAL') || die();

/**
 * Class injector
 *
 * @package     mod_xfgon
 * @author      David Bezemer <info@davidbezemer.nl>
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   David Bezemer <info@davidbezemer.nl>, www.davidbezemer.nl
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class injector {
    /** @var bool */
    private static $injected = false;

    public static function inject() {
        if (self::$injected) {
            return;
        }
        self::$injected = true;

        $engine = null;

        $xfgon = get_config('mod_xfgon', 'xfgon');
        //Add x5gon activation
        $xfgontypes = array('guniversal', 'gxfgon', 'piwik', 'x5gon');

        foreach ($xfgontypes as $type) {
            $enabled = get_config('mod_xfgon', $type);
            if ($enabled) {
                $classname = "\\mod_xfgon\\api\\{$type}";
                if (!class_exists($classname, true)) {
                    debugging("Local xfgon Module: xfgon setting '{$type}' doesn't map to a class name.");
                    return;
                }



                $engine = new $classname;
                $engine::insert_tracking();
            }
        }
    }

    public static function reset() {
        self::$injected = false;
        //debugging("This is a debug msg:");
    }
}
