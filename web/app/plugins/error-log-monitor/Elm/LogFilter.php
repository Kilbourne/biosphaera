<?php

interface Elm_LogFilter {

	/**
	 * Get the number of log entries that were skipped (i.e. filtered out) by this filter.
	 *
	 * @return int
	 */
	public function getSkippedEntryCount();
}