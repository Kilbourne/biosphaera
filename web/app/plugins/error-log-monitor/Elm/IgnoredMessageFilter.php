<?php
class Elm_IgnoredMessageFilter extends FilterIterator implements Elm_LogFilter {
	private $skippedEntryCount = 0;
	private $ignoredMessageIndex = array();

	public function __construct(Iterator $iterator, $ignoredMessages) {
		parent::__construct($iterator);
		$this->ignoredMessageIndex = $ignoredMessages;
	}

	/**
	 * Check whether the current element of the iterator is acceptable
	 *
	 * @return bool true if the current element is acceptable, otherwise false.
	 */
	public function accept() {
		$entry = $this->getInnerIterator()->current();
		if ( !isset($entry, $entry['message']) ) {
			return true;
		}

		if ( isset($this->ignoredMessageIndex[$entry['message']]) ) {
			$this->skippedEntryCount++;
			return false;
		} else {
			return true;
		}
	}

	public function getSkippedEntryCount() {
		$count = $this->skippedEntryCount;

		$inner = $this->getInnerIterator();
		if ( $inner instanceof Elm_LogFilter ) {
			$count += $inner->getSkippedEntryCount();
		}

		return $count;
	}

	public function rewind() {
		$this->skippedEntryCount = 0;
		parent::rewind();
	}
}