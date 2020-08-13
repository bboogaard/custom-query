<?php

namespace CustomQuery;

use \Puc_v4_Factory;

class UpdatesHandler {

    public function __construct() {

        Puc_v4_Factory::buildUpdateChecker(
        	'https://bramboogaard.nl/repo/custom-query.json',
        	CUSTOM_QUERY_PATH,
        	'custom-query'
        );

    }

}

class Updates {

    public function register() {

        $updates_handler = new UpdatesHandler();

    }

}
