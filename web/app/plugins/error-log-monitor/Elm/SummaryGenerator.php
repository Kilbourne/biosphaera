<?php
class Elm_SummaryGenerator {
	private $logIterator;
	private $timePeriod;
	private $binCount = 1;

	private $periodStart = 0;
	private $periodEnd = 0;

	public function __construct(Iterator $logIterator, $timePeriod, $chartBinCount) {
		$this->logIterator = $logIterator;
		$this->timePeriod = $timePeriod;
		$this->binCount = $chartBinCount;
	}

	public function getSummary($maxDistinctItems = 500) {
		$this->periodEnd = time();
		$this->periodStart = $this->periodEnd - $this->timePeriod;

		$summary = array(); /** @var Elm_SummaryItem[] $summary */

		foreach($this->logIterator as $entry) {
			//Have we reached the beginning of the specified period?
			if ( !empty($entry['timestamp']) && ($entry['timestamp'] < $this->periodStart) ) {
				printf(
					'Time threshold reached: %s<br>',
					date('c', $entry['timestamp'])
				);
				break;
			}

			$id = $entry['message'];
			if ( !empty($entry['stacktrace']) ) {
				$id .= "\n" . implode("\n", $entry['stacktrace']);
			}

			if ( !isset($summary[$id]) ) {
				//Avoid running out of memory.
				if ( count($summary) >= $maxDistinctItems ) {
					echo 'Max distinct items reached<br>';
					break;
				}
				$summary[$id] = new Elm_SummaryItem($entry, $this->binCount);
			}
			$item = $summary[$id];

			$timestamp = !empty($entry['timestamp']) ? $entry['timestamp'] : $this->periodEnd;
			$item->addEvent($timestamp, $this->getBinFor($timestamp));
		}

		return array_values($summary);
	}

	private function getBinFor($timestamp) {
		$index = intval(floor(
			($timestamp - $this->periodStart) / ($this->timePeriod) * $this->binCount
		));
		return max(min($index, $this->binCount - 1), 0);
	}
}