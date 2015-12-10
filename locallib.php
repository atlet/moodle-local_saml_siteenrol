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
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/user/selector/lib.php');
require_once($CFG->dirroot . '/enrol/locallib.php');

/**
 * Enrol candidates.
 */
class enrol_manual_potential_participant extends user_selector_base {

    protected $enrolid;
    protected $rules;

    public function __construct($name, $options) {
        $this->enrolid = $options['enrolid'];
        $this->rules = $options['rules'];
        parent::__construct($name, $options);
    }

    /**
     * Candidate users
     * @param string $search
     * @return array
     */
    public function find_users($search) {
        global $DB;
        $rules = $this->get_options();
        $rules = $rules['rules'];
        // By default wherecondition retrieves all users except the deleted, not confirmed and guest.
        list($wherecondition, $params) = $this->search_sql($search, 'u');
        $params['enrolid'] = $this->enrolid;

        $additionalWhere = array();

        $allrules = $DB->get_records('local_saml_site_rules', array('local_saml_site_id' => $rules->id));

        if ($allrules) {
            foreach ($allrules as $value) {
                switch ($value->ruletype) {
                    case 1:
                        $additionalWhere[] = "substring_index(username, '@', -1) = '" . $value->rule . "'";
                        break;

                    default:
                        break;
                }
            }
        }

        $fields = 'SELECT ' . $this->required_fields_sql('u');
        $countfields = 'SELECT COUNT(1)';

        if (!empty($additionalWhere)) {
            $additionalWhere = " AND (" . implode(' OR ', $additionalWhere) . ")";
        } else {
            $additionalWhere = '';
        }

        $sql = " FROM {user} u
            LEFT JOIN {user_enrolments} ue ON (ue.userid = u.id AND ue.enrolid = :enrolid)
                WHERE $wherecondition $additionalWhere
                      AND ue.id IS NULL";

        list($sort, $sortparams) = users_order_by_sql('u', $search, $this->accesscontext);
        $order = ' ORDER BY ' . $sort;

        if (!$this->is_validating()) {
            $potentialmemberscount = $DB->count_records_sql($countfields . $sql, $params);
            if ($potentialmemberscount > $this->maxusersperpage) {
                return $this->too_many_results($search, $potentialmemberscount);
            }
        }

        $availableusers = $DB->get_records_sql($fields . $sql . $order, array_merge($params, $sortparams));

        if (empty($availableusers)) {
            return array();
        }


        if ($search) {
            $groupname = get_string('enrolcandidatesmatching', 'enrol', $search);
        } else {
            $groupname = get_string('enrolcandidates', 'enrol');
        }

        return array($groupname => $availableusers);
    }

    protected function get_options() {
        $options = parent::get_options();
        $options['enrolid'] = $this->enrolid;
        $options['file'] = 'local/saml_siteenrol/locallib.php';
        $options['rules'] = $this->rules;
        return $options;
    }

}

/**
 * Enrolled users.
 */
class enrol_manual_current_participant extends user_selector_base {

    protected $courseid;
    protected $enrolid;

    public function __construct($name, $options) {
        $this->enrolid = $options['enrolid'];
        parent::__construct($name, $options);
    }

    /**
     * Candidate users
     * @param string $search
     * @return array
     */
    public function find_users($search) {
        global $DB;
        // By default wherecondition retrieves all users except the deleted, not confirmed and guest.
        list($wherecondition, $params) = $this->search_sql($search, 'u');
        $params['enrolid'] = $this->enrolid;

        $fields = 'SELECT ' . $this->required_fields_sql('u');
        $countfields = 'SELECT COUNT(1)';

        $sql = " FROM {user} u
                 JOIN {user_enrolments} ue ON (ue.userid = u.id AND ue.enrolid = :enrolid)
                WHERE $wherecondition";

        list($sort, $sortparams) = users_order_by_sql('u', $search, $this->accesscontext);
        $order = ' ORDER BY ' . $sort;

        if (!$this->is_validating()) {
            $potentialmemberscount = $DB->count_records_sql($countfields . $sql, $params);
            if ($potentialmemberscount > $this->maxusersperpage) {
                return $this->too_many_results($search, $potentialmemberscount);
            }
        }

        $availableusers = $DB->get_records_sql($fields . $sql . $order, array_merge($params, $sortparams));

        if (empty($availableusers)) {
            return array();
        }


        if ($search) {
            $groupname = get_string('enrolledusersmatching', 'enrol', $search);
        } else {
            $groupname = get_string('enrolledusers', 'enrol');
        }

        return array($groupname => $availableusers);
    }

    protected function get_options() {
        $options = parent::get_options();
        $options['enrolid'] = $this->enrolid;
        $options['file'] = 'local/saml_siteenrol/locallib.php';
        return $options;
    }

}
