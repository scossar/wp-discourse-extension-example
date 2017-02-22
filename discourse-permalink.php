<?php

namespace WPDiscourse\TopicLink;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

class DiscoursePermalink {
	protected $extended_options;

	public function __construct() {
		add_filter( 'the_content', array( $this, 'discourse_permalink' ) );
		add_filter( 'discourse/utilities/options-array', array( $this, 'add_options' ) );
	}

	public function add_options( $discourse_options ) {
		static $options = [];

		if ( empty( $extended_options ) ) {
			$added_options          = get_option( 'wpdc-topic-link' );
			$options                = array_merge( $discourse_options, $added_options );
			$this->extended_options = $options;
		}

		return $options;
	}

	public function discourse_permalink( $content ) {
		global $post;
		$post_id = $post->ID;

		// Todo: check if 'add-topic-link' is set.
		if ( is_single() && empty( $this->extended_options['use-discourse-comments'] ) &&
		     1 === intval( get_post_meta( $post_id, 'publish_to_discourse', true ) )
		) {
			$link_text     = ! empty( $this->extended_options['topic-link-text'] ) ? $this->extended_options['topic-link-text'] : '';
			$permalink     = get_post_meta( $post_id, 'discourse_permalink', true );
			$comment_count = $this->topic_comments_number( $permalink );
			if ( empty( $comment_count ) ) {
				$comment_text = '0 Comments';
			} elseif ( 1 === intval( $comment_count ) ) {
				$comment_text = '1 Comment';
			} else {
				$comment_text = $comment_count . ' Comments';
			}
			$topic_link = '<div class="discourse-topic-link"><a href="' . esc_url( $permalink ) . '">' . sanitize_text_field( $link_text ) . ' / ' . $comment_text . '</a></div>';

			return $content . $topic_link;
		}

		return $content;
	}

	protected function topic_comments_number( $permalink ) {
		$permalink      = $permalink . '.json';
		$comments_count = null;
		$response       = wp_remote_get( $permalink );
		if ( DiscourseUtilities::validate( $response ) ) {
			$response       = json_decode( wp_remote_retrieve_body( $response ) );
			$comments_count = $response->posts_count - 1;
		}

		return $comments_count;
	}
}