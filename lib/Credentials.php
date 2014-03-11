<?php

/**
 * class Credentials
 *
 * Provides a wrapper for username/password
 *
 * @author Shlomi Noach <snoach@outbrain.com>
 * @license Apache 2.0 license.  See LICENSE document for more info
 * @created 2013-10-25
 */
class Credentials {

    private $username;
    private $password;

    /**
     * Constructor.  Initialize the model object
     *
     * @param array $conf   The global config information
     */
    function __construct($username, $password) {
        $this->username = $username;
        $this->password = $password;
    }

    public function is_empty() {
        return empty($this->username);
    }

    public function get_username() {
       	return $this->username;
    }

    public function get_password() {
       	return $this->password;
    }
}

?>
