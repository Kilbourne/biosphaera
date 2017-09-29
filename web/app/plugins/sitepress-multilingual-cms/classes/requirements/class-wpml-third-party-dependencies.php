<?php

/**
 * @author OnTheGo Systems
 */
class WPML_Third_Party_Dependencies {
	private $page_builders;
	private $requirements;

	/**
	 * WPML_Third_Party_Dependencies constructor.
	 *
	 * @param WPML_Page_Builders        $page_builders
	 * @param WPML_Requirements         $requirements
	 */
	public function __construct( WPML_Page_Builders $page_builders, WPML_Requirements $requirements ) {
		$this->page_builders        = $page_builders;
		$this->requirements         = $requirements;
	}

	public function get_issues() {
		$issues = array();

		$components = $this->page_builders->get_results();
		foreach ( (array) $components as $slug => $component_data ) {
			$issue = $this->get_issue( $component_data );
			if ( $issue ) {
				$issues[] = $issue;
			}
		}

		if ( ! $issues ) {
			return array();
		}

		return array( 'issues' => $issues );
	}

	private function get_issue( $component_data ) {
		$requirements = $this->requirements->get_requirements( $component_data['type'] );
		if ( ! $requirements ) {
			return null;
		}

		return array(
			'cause'        => $component_data,
			'requirements' => $requirements,
		);
	}
}
