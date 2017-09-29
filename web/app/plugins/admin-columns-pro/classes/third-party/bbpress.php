<?php

class cpac_bbpress_support {

	function __construct() {
		add_filter( 'cac/storage_models', array( $this, 'set_menu_type' ) );
		add_filter( 'cac/default_column_names', array( $this, 'default_column_names' ), 10, 2 );
	}

	public function set_menu_type( $storage_models ) {
		if ( class_exists( 'bbPress', false ) ) {
			$post_types = array_keys( $this->get_bbpress_columns() );
			foreach ( $storage_models as $k => $storage_model ) {
				if ( in_array( $storage_model->get_post_type(), $post_types ) ) {
					$storage_models[ $k ] = $storage_model->set_menu_type( __( 'bbPress', 'codepress-admin-columns' ) );
				}
			}
		}

		return $storage_models;
	}

	private function get_bbpress_columns() {
		$column_names = array(
			'forum' => array(
				'cb',
				'title',
				'bbp_forum_topic_count',
				'bbp_forum_reply_count',
				'author',
				'bbp_forum_created',
				'bbp_forum_freshness'
			),
			'topic' => array(
				'cb',
				'title',
				'bbp_topic_forum',
				'bbp_topic_reply_count',
				'bbp_topic_voice_count',
				'bbp_topic_author',
				'bbp_topic_created',
				'bbp_topic_freshness'
			),
			'reply' => array(
				'cb',
				'title',
				'bbp_reply_forum',
				'bbp_reply_topic',
				'bbp_reply_author',
				'bbp_reply_created'
			)
		);

		return $column_names;
	}

	public function default_column_names( $column_names, $storage_model ) {
		$bb_column_names = $this->get_bbpress_columns();

		return isset( $bb_column_names[ $storage_model->key ] ) ? $bb_column_names[ $storage_model->key ] : $column_names;
	}
}

new cpac_bbpress_support;