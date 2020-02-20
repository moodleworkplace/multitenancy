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
 * Manage tenants
 *
 * @package     tool_tenant
 * @copyright   2018 Moodle Pty Ltd <support@moodle.com>
 * @author      2018 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @license     Moodle Workplace License, distribution is restricted, contact support@moodle.com
 */

require_once(__DIR__ . '/../../../config.php');

$action = optional_param('action', null, PARAM_ALPHA);
$id = optional_param('id', null, PARAM_INT);

require_login(0, false);
require_capability('tool/tenant:manage', context_system::instance());
$PAGE->set_context(context_system::instance());
$PAGE->set_url(\tool_tenant\manager::get_base_url());

$PAGE->set_heading(get_string('managetenants', 'tool_tenant'));
echo $OUTPUT->header();

echo $OUTPUT->footer();