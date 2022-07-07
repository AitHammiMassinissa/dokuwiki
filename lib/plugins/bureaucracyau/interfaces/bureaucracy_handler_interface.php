<?php

namespace dokuwiki\plugin\bureaucracyau\interfaces;

interface bureaucracyau_handler_interface {

    /**
     * Handle the data incoming from the form.
     *
     * @param \helper_plugin_bureaucracyau_field[] $fields the list of fields in the form
     * @param string                             $thanks the thank you message as defined in the form
     *                                                   or default one. Might be modified by the action
     *                                                   before returned
     *
     * @return bool|string false on error, $thanks on success
     *
     */
    public function handleData($fields, $thanks);
}
