<?php
class Elm_ReverseLogParser implements OuterIterator {
	/**
	 * @var array Recognized error levels. See PHP source code: /main/main.c, function php_error_cb.
	 * The "unknown error" case was intentionally omitted.
	 */
	private static $builtinSeverityLevels = array(
		'fatal error' => true,
		'catchable fatal error' => true,
		'parse error' => true,
		'warning' => true,
		'notice' => true,
		'strict standards' => true,
		'deprecated' => true,
	);

	/**
	 * @var Iterator
	 */
	private $lineIterator;
	private $currentEntry = null;
	private $currentKey = 0;

	/**
	 * @var array A circular buffer used to implement backtracking.
	 */
	private $backtrackBuffer = array();
	const BACKTRACKING_BUFFER_SIZE = 100;

	/**
	 * @var int Next read index. Must not exceed the write index.
	 */
	private $bufferReadIndex = 0;

	/**
	 * @var int Next write index.
	 */
	private $bufferWriteIndex = 0;

	private $backtrackingIndexStack = array();

	/**
	 * @var bool Attempt to parse XDebug stack traces.
	 */
	private $isXdebugTraceEnabled = false;

	/**
	 * @var bool Attempt to parse stack traces that PHP 7 generates for fatal errors.
	 */
	private $isPhpDefaultTraceEnabled = false;

	public function __construct(Iterator $lineIterator) {
		$this->lineIterator = $lineIterator;
		$this->isXdebugTraceEnabled = function_exists('extension_loaded') && extension_loaded('xdebug');
		$this->isPhpDefaultTraceEnabled = version_compare(phpversion(), '5.4', '>=');
	}

	/**
	 * Read the next entry from the log and store it in $currentEntry.
	 */
	private function readNextEntry() {
		$this->currentEntry = null;
		if ( !$this->lineIterator->valid() && empty($this->backtrackBuffer) ) {
			return;
		}

		$this->currentKey++;

		//Try to read a log entry with an XDebug stack trace.
		if ( $this->isXdebugTraceEnabled ) {
			$this->saveState();
			$this->currentEntry = $this->parseEntryWithXdebugTrace();
			if ( $this->currentEntry !== null ) {
				$this->complete();
				return;
			} else {
				$this->backtrack();
			}
		}

		//Try to read an entry with PHP7-style stack trace.
		if ( !isset($this->currentEntry) && $this->isPhpDefaultTraceEnabled ) {
			$this->saveState();
			$this->currentEntry = $this->parseEntryWithStackTrace();
			if ( $this->currentEntry !== null ) {
				$this->complete();
				return;
			} else {
				$this->backtrack();
			}
		}

		//Try to read a normal log entry.
		$this->currentEntry = $this->readParsedLine();
	}

	private function parseEntryWithXdebugTrace() {
		$stackTraceRegex = '/^PHP[ ]{1,5}?(\d{1,3}?)\.\s./';
		$stackTrace = null;

		$line = $this->readParsedLine();
		if ( isset($line) && preg_match($stackTraceRegex, $line['message'], $matches) ) {
			$stackTrace = array($line['message']);
			$remainingTraceLines = intval($matches[1]) - 1;
		} else {
			return null;
		}

		for ( $traceIndex = $remainingTraceLines; $traceIndex > 0; $traceIndex-- ) {
			$line = $this->readParsedLine();
			if ( isset($line) && preg_match($stackTraceRegex, $line['message'], $matches) && (intval($matches[1]) === $traceIndex) ) {
				$stackTrace[] = $line['message'];
			} else {
				return null;
			}
		}

		$line = $this->readParsedLine();
		if ( isset($line) && ($line['message'] == 'PHP Stack trace:' ) ) {
			$stackTrace[] = $line['message'];
		} else {
			return null;
		}

		$entry = $this->readParsedLine();
		if ( $entry === null ) {
			return null;
		}

		$entry['stacktrace'] = array_reverse($stackTrace);
		return $entry;
	}

	private function parseEntryWithStackTrace() {
		$stackTrace = array();

		//The last line of the stack trace can be "#123 /path/to/x.php..." or "  thrown in /path/to/x.php..."
		$line = $this->readNextLine();
		if ( isset($line) && preg_match('/^(\s\sthrown in |#\d{1,3}\s\S)/', $line) ) {
			$stackTrace[] = $line;
		} else {
			return null;
		}

		//Read until we find a line with a timestamp. That's the first line.
		$traceLimit = 50;
		do {
			$entry = $this->readParsedLine();
			if ( !isset($entry) ) {
				return null;
			}

			if ( empty($entry['timestamp']) ) {
				$stackTrace[] = $entry['message'];
			} else {
				$stackTrace = array_reverse($stackTrace);
				//The stack trace always starts with "Stack trace:" on its own line.
				if ( $stackTrace[0] === 'Stack trace:' ) {
					$entry['stacktrace'] = $stackTrace;
					return $entry;
				} else {
					return null;
				}
			}
		} while ( count($stackTrace) < $traceLimit );

		return null;
	}

	/**
	 * Save the current read state for later backtracking.
	 */
	private function saveState() {
		$this->backtrackingIndexStack[] = $this->bufferReadIndex;
	}

	/**
	 * Backtrack to the last saved state.
	 */
	private function backtrack() {
		if ( empty($this->backtrackingIndexStack) ) {
			throw new LogicException('Tried to backtrack but the stack is empty!');
		}
		$this->bufferReadIndex = array_pop($this->backtrackingIndexStack);
	}

	/**
	 * Discard the last saved backtracking state. Call this when parsing succeeds.
	 */
	private function complete() {
		array_pop($this->backtrackingIndexStack);
	}

	/**
	 * Read a single line from the log, parsed into basic components (timestamp, the message itself, etc).
	 *
	 * @param bool $skipEmptyLines
	 * @return array|null
	 */
	private function readParsedLine($skipEmptyLines = true) {
		$line = $this->readNextLine($skipEmptyLines);
		if ( $line === null ) {
			return null;
		}
		return $this->parseLogLine($line);
	}

	private function parseLogLine($line) {
		$line = rtrim($line);
		$timestamp = null;
		$message = $line;
		$level = null;

		//We expect log entries to be structured like this: "[date-and-time] Optional severity: error message".
		$pattern = '/
			^(?:\[(?P<timestamp>[\w \-+:]{6,50}?)\]\ )?
			(?P<message>
			    (?:(?:PHP\ )?(?P<severity>[a-zA-Z][a-zA-Z ]{3,40}?):\ )?
			.+)$
		/x';

		if ( preg_match($pattern, $line, $matches) ) {
			$message = $matches['message'];

			if ( !empty($matches['timestamp']) ) {
				//Attempt to parse the timestamp, if any. Timestamp format can vary by server.
				$parsedTimestamp = strtotime($matches['timestamp']);
				if ( !empty($parsedTimestamp) ) {
					$timestamp = $parsedTimestamp;
				};
			}

			if ( !empty($matches['severity']) ) {
				//Parse the severity level.
				$levelName = strtolower(trim($matches['severity']));
				if ( isset(self::$builtinSeverityLevels[$levelName]) ) {
					$level = $levelName;
				}
			}
		}

		return array(
			'message' => $message,
			'timestamp' => $timestamp,
			'level' => $level,
		);
	}

	/**
	 * Read a single line from the log.
	 *
	 * @param bool $skipEmptyLines
	 * @return string|null
	 */
	private function readNextLine($skipEmptyLines = true) {
		//Check the internal buffer first.
		while ( $this->bufferReadIndex < $this->bufferWriteIndex ) {
			$line = $this->backtrackBuffer[$this->bufferReadIndex % self::BACKTRACKING_BUFFER_SIZE];
			$this->bufferReadIndex++;

			if ( !$skipEmptyLines || ($line !== '') ) {
				return $line;
			}
		}

		//Then check the actual file iterator.
		while ( $this->lineIterator->valid() ) {
			$line = $this->lineIterator->current();
			$this->lineIterator->next();

			if ( !empty($this->backtrackingIndexStack) ) {
				$this->backtrackBuffer[$this->bufferWriteIndex % self::BACKTRACKING_BUFFER_SIZE] = $line;
				$this->bufferWriteIndex++;
				$this->bufferReadIndex = $this->bufferWriteIndex;

				if ( $this->bufferWriteIndex - $this->backtrackingIndexStack[0] > self::BACKTRACKING_BUFFER_SIZE ) {
					throw new RuntimeException('Backtrack buffer overflow');
				};
			}

			if ( !$skipEmptyLines || ($line !== '') ) {
				return $line;
			}
		}

		return null;
	}

	/**
	 * Return the current log entry.
	 *
	 * @return array
	 */
	public function current() {
		return $this->currentEntry;
	}

	/**
	 * Move forward to next log entry.
	 */
	public function next() {
		$this->readNextEntry();
	}

	/**
	 * Return the key of the current entry.
	 * The key is not actually used by the plugin, but it is required by the Iterator interface.
	 *
	 * @return int|null
	 */
	public function key() {
		return $this->currentKey;
	}

	/**
	 * Checks if current position is valid.
	 *
	 * @return boolean
	 */
	public function valid() {
		return isset($this->currentEntry);
	}

	/**
	 * Rewind the iterator to the last log entry.
	 */
	public function rewind() {
		$this->lineIterator->rewind();
		$this->currentKey = 0;

		$this->backtrackingIndexStack = array();
		$this->backtrackBuffer = array();
		$this->bufferReadIndex = 0;
		$this->bufferWriteIndex = 0;

		$this->readNextEntry();
	}

	/**
	 * Returns the inner iterator.
	 *
	 * @return Iterator
	 */
	public function getInnerIterator() {
		return $this->lineIterator;
	}
}