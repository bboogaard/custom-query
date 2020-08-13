<?php

namespace CustomQuery;

use \Puc_v4_Factory;

class UpdatesHandler {

    public function __construct() {

        Puc_v4_Factory::buildUpdateChecker(
        	'https://github.com/bboogaard/custom-query/blob/master/updates.json',
        	CUSTOM_QUERY_PATH,
        	'custom-query'
        );

    }

}

class Updates {

    public static function register() {

        $updates_handler = new UpdatesHandler();

    }

}
