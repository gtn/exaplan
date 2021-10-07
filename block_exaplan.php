<?php
class block_exaplan extends block_base {
    public function init() {
        $this->title = get_string('exaplan', 'block_exaplan');
    }

    public function get_content() {

        global $CFG;
        if ($this->content !== null) {
            return $this->content;
        }

        $this->content         =  new stdClass;
        $this->content->items = array();
        $this->content->text = '<a title="dashboard" href="'.$CFG->wwwroot.'/blocks/exaplan/dashboard.php">Dashboard</a>';

        return $this->content;
    }
    // The PHP tag and the curly bracket for the class definition
    // will only be closed after there is another function added in the next section.
}