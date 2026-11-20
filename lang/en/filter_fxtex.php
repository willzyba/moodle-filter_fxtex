<?php
// This file is part of Moodle - http://moodle.org/
//
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for filter_fxtex.
 *
 * @package     filter_fxtex
 * @copyright   2026 CodeCogs, Zyba Ltd. <https://www.codecogs.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['filtername'] = 'fxTeX maths';
$string['licence'] = 'Licence key';
$string['licence_desc'] = 'Your fxTeX Pro licence key (fx_pk_...), for chemical structures (chemfig), pictures (the picture environment) and the physics package, and faster typesetting. Get a 30-day trial key, or buy one, for this site\'s domain from https://fxeditor.codecogs.com/purchase. Left empty, the key given to the CodeCogs Equation Editor is used if there is one; otherwise equations are typeset with the free fxTeX, which has everything but those packages.';
$string['pluginname'] = 'fxTeX maths';
$string['privacy:metadata:fxtex'] = 'fxTeX and fxTeX Pro, which typeset the maths, are loaded into the reader\'s browser from fxtex.codecogs.com, run by CodeCogs, on each page that has maths on it. The plugin sends nothing there itself; the browser does.';
$string['privacy:metadata:fxtex:licence'] = 'The site\'s fxTeX Pro licence key, if it has one, which identifies the Moodle site, not the user.';
$string['privacy:metadata:fxtex:pageurl'] = 'The address of the Moodle page, which the browser sends as the referrer and which is used to check that the licence key is valid for the site.';
$string['server'] = 'fxTeX URL';
$string['server_desc'] = 'Where fxTeX and fxTeX Pro are loaded from: CodeCogs\' service, or your own copy of it.';
$string['singledollar'] = 'Single dollar signs';
$string['singledollar_desc'] = 'Also read `$...$` as inline maths, as LaTeX does. Off by default, as a single $ is common in ordinary text (prices, for one). `\\(...\\)`, `\\[...\\]` and `$$...$$` are always read.';
$string['texcompat'] = 'TeX filter compatibility';
$string['texcompat_desc'] = 'Also read the old TeX notation filter\'s `[tex]...[/tex]` and `<tex>...</tex>` as inline maths.';
