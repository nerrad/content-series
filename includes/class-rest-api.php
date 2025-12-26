<?php
/**
 * REST API extensions for Content Series.
 *
 * @package ContentSeries
 */

namespace ContentSeries;

/**
 * Handles custom REST API endpoints for series management.
 */
class Rest_API {

	/**
	 * REST API namespace.
	 */
	const NAMESPACE = 'content-series/v1';

	/**
	 * Initialize REST API hooks.
	 */
	public function init() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		add_filter( 'rest_prepare_series', array( $this, 'add_series_meta_to_response' ), 10, 3 );
	}

	/**
	 * Register custom REST routes.
	 */
	public function register_routes() {
		// Get posts in a series (ordered).
		register_rest_route(
			self::NAMESPACE,
			'/series/(?P<id>\d+)/posts',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_series_posts' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'id' => array(
						'required'          => true,
						'validate_callback' => function( $param ) {
							return is_numeric( $param );
						},
					),
				),
			)
		);

		// Update post order in a series.
		register_rest_route(
			self::NAMESPACE,
			'/series/(?P<id>\d+)/reorder',
			array(
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'reorder_series_posts' ),
				'permission_callback' => function() {
					return current_user_can( 'edit_posts' );
				},
				'args'                => array(
					'id'    => array(
						'required'          => true,
						'validate_callback' => function( $param ) {
							return is_numeric( $param );
						},
					),
					'order' => array(
						'required'          => true,
						'validate_callback' => function( $param ) {
							return is_array( $param );
						},
					),
				),
			)
		);

		// Get all series with full metadata.
		register_rest_route(
			self::NAMESPACE,
			'/series',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_all_series' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'per_page' => array(
						'default'           => 100,
						'validate_callback' => function( $param ) {
							return is_numeric( $param ) && $param > 0 && $param <= 100;
						},
					),
					'page'     => array(
						'default'           => 1,
						'validate_callback' => function( $param ) {
							return is_numeric( $param ) && $param > 0;
						},
					),
				),
			)
		);
	}

	/**
	 * Get posts in a series, ordered by series part.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function get_series_posts( $request ) {
		$series_id = absint( $request['id'] );

		// Verify series exists.
		$series = get_term( $series_id, CONTENT_SERIES_TAXONOMY );
		if ( ! $series || is_wp_error( $series ) ) {
			return new \WP_Error(
				'series_not_found',
				__( 'Series not found.', 'content-series' ),
				array( 'status' => 404 )
			);
		}

		// Get posts in series.
		$posts = $this->query_series_posts( $series_id );

		// Format response.
		$response_data = array();
		foreach ( $posts as $post ) {
			$response_data[] = array(
				'id'           => $post->ID,
				'title'        => get_the_title( $post->ID ),
				'short_title'  => get_post_meta( $post->ID, CONTENT_SERIES_SHORT_TITLE_KEY, true ),
				'url'          => get_permalink( $post->ID ),
				'series_part'  => Post_Meta::get_post_series_part( $post->ID, $series_id ),
				'status'       => $post->post_status,
				'date'         => $post->post_date,
			);
		}

		return rest_ensure_response( array(
			'series' => array(
				'id'          => $series->term_id,
				'name'        => $series->name,
				'slug'        => $series->slug,
				'description' => $series->description,
				'icon'        => Term_Meta::get_series_icon( $series->term_id ),
				'count'       => $series->count,
			),
			'posts'  => $response_data,
		) );
	}

	/**
	 * Reorder posts within a series.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object.
	 */
	public function reorder_series_posts( $request ) {
		$series_id = absint( $request['id'] );
		$order     = $request['order'];

		// Verify series exists.
		$series = get_term( $series_id, CONTENT_SERIES_TAXONOMY );
		if ( ! $series || is_wp_error( $series ) ) {
			return new \WP_Error(
				'series_not_found',
				__( 'Series not found.', 'content-series' ),
				array( 'status' => 404 )
			);
		}

		// Update order for each post.
		// $order should be array of { post_id: order_number }.
		foreach ( $order as $post_id => $position ) {
			Post_Meta::set_post_series_part( absint( $post_id ), $series_id, absint( $position ) );
		}

		return rest_ensure_response( array(
			'success' => true,
			'message' => __( 'Series order updated.', 'content-series' ),
		) );
	}

	/**
	 * Get all series with metadata.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response Response object.
	 */
	public function get_all_series( $request ) {
		$per_page = absint( $request['per_page'] );
		$page     = absint( $request['page'] );
		$offset   = ( $page - 1 ) * $per_page;

		$terms = get_terms( array(
			'taxonomy'   => CONTENT_SERIES_TAXONOMY,
			'hide_empty' => false,
			'number'     => $per_page,
			'offset'     => $offset,
			'orderby'    => 'name',
			'order'      => 'ASC',
		) );

		$total = wp_count_terms( array(
			'taxonomy'   => CONTENT_SERIES_TAXONOMY,
			'hide_empty' => false,
		) );

		if ( is_wp_error( $terms ) ) {
			$terms = array();
		}

		$response_data = array();
		foreach ( $terms as $term ) {
			$response_data[] = array(
				'id'          => $term->term_id,
				'name'        => $term->name,
				'slug'        => $term->slug,
				'description' => $term->description,
				'icon'        => Term_Meta::get_series_icon( $term->term_id ),
				'count'       => $term->count,
				'link'        => get_term_link( $term ),
			);
		}

		$response = rest_ensure_response( $response_data );
		$response->header( 'X-WP-Total', $total );
		$response->header( 'X-WP-TotalPages', ceil( $total / $per_page ) );

		return $response;
	}

	/**
	 * Add series meta to REST response.
	 *
	 * @param \WP_REST_Response $response Response object.
	 * @param \WP_Term          $term     Term object.
	 * @param \WP_REST_Request  $request  Request object.
	 * @return \WP_REST_Response Modified response.
	 */
	public function add_series_meta_to_response( $response, $term, $request ) {
		$data = $response->get_data();

		// Add icon to response.
		$data['icon'] = Term_Meta::get_series_icon( $term->term_id );

		$response->set_data( $data );
		return $response;
	}

	/**
	 * Query posts in a series, ordered by series part.
	 *
	 * @param int  $series_id      Series term ID.
	 * @param bool $published_only Whether to only include published posts.
	 * @return array Array of post objects.
	 */
	public static function query_series_posts( $series_id, $published_only = true ) {
		$meta_key = CONTENT_SERIES_PART_KEY . '_' . $series_id;

		$args = array(
			'post_type'      => 'post',
			'posts_per_page' => -1,
			'tax_query'      => array(
				array(
					'taxonomy' => CONTENT_SERIES_TAXONOMY,
					'field'    => 'term_id',
					'terms'    => $series_id,
				),
			),
			'meta_key'       => $meta_key,
			'orderby'        => 'meta_value_num',
			'order'          => 'ASC',
		);

		if ( $published_only ) {
			$args['post_status'] = 'publish';
		} else {
			$args['post_status'] = array( 'publish', 'draft', 'pending', 'future' );
		}

		return get_posts( $args );
	}
}
