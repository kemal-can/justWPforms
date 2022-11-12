<?php

class justwpforms_Task {

    public static $event;
    public $response_id;

    public function __construct( $response_id ) {
        $this->response_id = $response_id;
    }

    public static function get_event() {
        $event = 'justwpforms_task_' . static::$event;
        return $event;
    }

    public function run() {
        // noop
    }

}