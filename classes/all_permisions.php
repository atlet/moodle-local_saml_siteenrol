<?php

defined('MOODLE_INTERNAL') || die;

class all_permisions extends table_sql {

    var $rulestype = array();    
    
    /**
     * Constructor
     * @param int $uniqueid all tables have to have a unique id, this is used
     *      as a key when storing table properties like sort order in the session.
     */
    function __construct($uniqueid) {
        parent::__construct($uniqueid);
        // Define the list of columns to show.
        $columns = array(
            'name', 
            'selected'
        );
        $this->define_columns($columns);

        // Define the titles of columns to show in header.
        $headers = array(
            get_string('selectcategory', 'local_saml_siteenrol'),
            ''
        );
        $this->define_headers($headers);

        $this->collapsible(false);
        $this->sortable(true);
        $this->pageable(true);
        
        $this->rulestype[1] = get_string('usernamedomainname', 'local_saml_siteenrol'); 
    }

    /**
     * This function is called for each data row to allow processing of the
     * username value.
     *
     * @param object $values Contains object with all the values of record.
     * @return $string Return username with link to profile or username only
     *     when downloading.
     */
    function col_ruletype($values) {        
        return $this->rulestype[$values->ruletype];
    }

    function col_selected($values) {
        if (!$this->is_downloading()) {
            $permissions = "<a href=\"listpermission.php?cid=$values->id\">" . get_string('permissions', 'local_saml_siteenrol') . '</a>';
            
            return "{$permissions}";
        } else {
            return '';
        }
    }

    /**
     * This function is called for each data row to allow processing of
     * columns which do not have a *_cols function.
     * @return string return processed value. Return NULL if no change has
     *     been made.
     */
    function other_cols($colname, $value) {
        
    }

}
