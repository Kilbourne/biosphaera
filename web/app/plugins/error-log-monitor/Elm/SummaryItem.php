<?php

class Elm_SummaryItem {
	public $message = '';
	public $stackTrace = array();

	public $lastSeenTimestamp = 0;
	public $firstSeenTimestamp = 0;

	public $chart = array();
	public $count = 0;

	public function __construct($logEntry, $binCount = 10) {
		$this->message = $logEntry['message'];
		$this->chart = array_fill(0, $binCount, 0);

		if ( !empty($logEntry['timestamp']) ) {
			$this->lastSeenTimestamp = $logEntry['timestamp'];
			$this->firstSeenTimestamp = $this->lastSeenTimestamp;
		}

		if ( !empty($logEntry['stacktrace']) ) {
			$this->stackTrace = $logEntry['stacktrace'];
		}
	}

	public function addEvent($timestamp = null, $binIndex = null) {
		$this->count++;

		if ( isset($timestamp) ) {
			$this->lastSeenTimestamp = max($this->lastSeenTimestamp, $timestamp);
			$this->firstSeenTimestamp = min($this->firstSeenTimestamp, $timestamp);
		}

		if ( isset($binIndex) ) {
			$this->chart[$binIndex]++;
		}
	}
}