<?php

namespace CustomQuery;

use \Redis;
use \UUID;

class MissingValue {

}

class QuerySchema {

    protected $fields;

    public function __construct($fields) {

        $this->fields = $fields;

    }

    public function from_redis($data) {

        $args = array();
        foreach ($this->fields as $field) {
            $value = isset($data[$field->name]) ? $data[$field->name] : new MissingValue();
            if ($value instanceof MissingValue) {
                continue;
            }
            $args[$field->name] = $field->from_redis($value);
        }
        return $args;

    }

    public function to_redis($args) {

        $data = array();
        foreach ($this->fields as $field) {
            $value = isset($args[$field->name]) ? $args[$field->name] : new MissingValue();
            if ($value instanceof MissingValue) {
                continue;
            }
            $data[$field->name] = $field->to_redis($value);
        }
        return $data;

    }

}

class QueryField {

    public $name;

    public $type = 'string';

    public function __construct($name) {

        $this->name = $name;

    }

    public function from_redis($value) {

        return $value;

    }

    public function to_redis($value) {

        return $value;

    }

}

class IntField extends QueryField {

    public $type = 'integer';

    public function from_redis($value) {

        return intval($value);

    }

    public function to_redis($value) {

        return strval($value);

    }

}

class ArrayField extends QueryField {

    public $type = 'array';

    public function from_redis($value) {

        return unserialize($value);

    }

    public function to_redis($value) {

        return serialize($value);

    }

}

class PersistentQuery {

    private $is_connected, $key_prefix, $qid, $query_fields, $redis, $redis_db,
    $redis_host, $redis_port;

    public function __construct(Redis $redis,
                                $query_fields,
                                $key_prefix,
                                $redis_host='127.0.0.1',
                                $redis_port=6379,
                                $redis_db=0) {

        $this->redis = $redis;
        $this->query_fields = $query_fields;
        $this->key_prefix = $key_prefix;
        $this->redis_host = $redis_host;
        $this->redis_port = $redis_port;
        $this->redis_db = $redis_db;

        $this->is_connected = false;

    }

    public function load($qid) {

        $this->connect();
        if ($data = $this->redis->hgetall($this->make_key($qid))) {
            return $this->from_redis($data);
        }
        return false;

    }

    public function save($args) {

        $qid = UUID::v4();
        $data = $this->to_redis($args);
        $this->connect();
        $this->redis->hset(
            $this->make_key($qid), 'fields', serialize($this->query_fields)
        );
        foreach ($data as $field => $value) {
            $this->redis->hset($this->make_key($qid), $field, $value);
        }
        return $qid;

    }

    private function connect() {

        if ($this->is_connected) {
            return;
        }

        $this->redis->connect($this->redis_host, $this->redis_port);
        $this->redis->select($this->redis_db);
        $this->is_connected = true;

    }

    private function make_key($qid) {

        return sprintf('%s:%s', $this->key_prefix, $qid);

    }

    private function from_redis($data) {

        $query_fields = unserialize($data['fields']);
        unset($data['fields']);
        $query_schema = $this->get_query_schema($query_fields);
        return $query_schema->from_redis($data);

    }

    private function to_redis($args) {

        $query_schema = $this->get_query_schema($this->query_fields);
        return $query_schema->to_redis($args);

    }

    private function get_query_schema($query_fields) {

        $_query_fields = array();
        foreach ($query_fields as $field_name => $field_type) {
            $field_class = $this->get_field_class($field_type);
            $field = new $field_class($field_name);
            array_push(
                $_query_fields,
                $field
            );
        }

        return new QuerySchema($_query_fields);

    }

    private static function get_field_class($field_type) {

        switch ($field_type) {
            case 'integer':
                return IntField::class;
            case 'array':
                return ArrayField::class;
            default:
                return QueryField::class;
        }
    }

}

class PersistentQueryFactory {

    public static function create($query_fields) {

        $options = get_option('custom_query_options', array(
            'redis_host' => '127.0.0.1',
            'redis_port' => 6379,
            'redis_db' => 0
        ));

        $redis = new Redis();

        return new PersistentQuery(
            $redis,
            $query_fields,
            'cq:qid',
            $options['redis_host'],
            $options['redis_port'],
            $options['redis_db']
        );

    }

}
