<?php
declare(strict_types=1);

namespace AcfQuickEditColumns;

/**
 * Class FieldValueFormatter
 *
 * Formats and retrieves ACF field values for use in Quick Edit columns.
 */
class FieldValueFormatter {
	/**
	 * @var string Field name.
	 */
	private string $field_name;

	/**
	 * @var array Field settings array.
	 */
	private array $field;

	/**
	 * @var string Field type.
	 */
	private string $field_type;

	/**
	 * @var int Post ID.
	 */
	private int $post_id;

	/**
	 * FieldValueFormatter constructor.
	 *
	 * @param string $field_name The ACF field name.
	 * @param int    $post_id    The post ID.
	 */
	public function __construct( string $field_name, int $post_id ) {
		$this->field_name = $field_name;
		$this->post_id    = $post_id;

		$field            = acf_get_field( $field_name );
		$this->field      = $field ? $field : array();
		$this->field_type = isset( $field['type'] ) ? $field['type'] : '';
		if ( ! $this->field ) {
			error_log( "ACF Quick Edit Columns: Field {$field_name} not found for post {$post_id}" );
		}
	}

	/**
	 * Get value for text field.
	 *
	 * @return string
	 */
	private function text(): string {
		$value = get_field( $this->field_name, $this->post_id );
		return esc_html( $value ? $value : '' );
	}

	/**
	 * Get value for textarea field.
	 *
	 * @return string
	 */
	private function textarea(): string {
		$value = get_field( $this->field_name, $this->post_id );
		return wp_kses_post( $value ? $value : '' );
	}

	/**
	 * Get value for select field.
	 *
	 * @return array|string
	 */
	private function select() {
		$value = get_field( $this->field_name, $this->post_id );
		return is_array( $value ) ? array_map( 'esc_html', $value ) : esc_html( $value ? $value : '' );
	}

	/**
	 * Get value for checkbox field.
	 *
	 * @return array
	 */
	private function checkbox(): array {
		$value = get_field( $this->field_name, $this->post_id );
		return is_array( $value ) ? array_map( 'esc_html', $value ) : array();
	}

	/**
	 * Get value for image field.
	 *
	 * @return array
	 */
	private function image(): array {
		$value = get_field( $this->field_name, $this->post_id );
		if ( ! $value ) {
			return array();
		}
		$image = is_array( $value ) ? $value : wp_get_attachment_image_src( $value, 'thumbnail' );
		return array(
			'id'    => is_array( $value ) ? ( isset( $value['ID'] ) ? $value['ID'] : '' ) : $value,
			'url'   => is_array( $value ) ? ( isset( $value['url'] ) ? $value['url'] : '' ) : ( isset( $image[0] ) ? $image[0] : '' ),
			'title' => is_array( $value ) ? ( isset( $value['title'] ) ? $value['title'] : '' ) : get_the_title( $value ),
		);
	}

	/**
	 * Get value for file field.
	 *
	 * @return array
	 */
	private function file(): array {
		$value = get_field( $this->field_name, $this->post_id );
		if ( ! $value ) {
			return array();
		}
		return array(
			'id'    => is_array( $value ) ? ( isset( $value['ID'] ) ? $value['ID'] : '' ) : $value,
			'url'   => is_array( $value ) ? ( isset( $value['url'] ) ? $value['url'] : '' ) : wp_get_attachment_url( $value ),
			'title' => is_array( $value ) ? ( isset( $value['title'] ) ? $value['title'] : '' ) : get_the_title( $value ),
		);
	}

	/**
	 * Get value for URL field.
	 *
	 * @return string
	 */
	private function url(): string {
		$value = get_field( $this->field_name, $this->post_id );
		return esc_url( $value ? $value : '' );
	}

	/**
	 * Get value for email field.
	 *
	 * @return string
	 */
	private function email(): string {
		$value = get_field( $this->field_name, $this->post_id );
		return sanitize_email( $value ? $value : '' );
	}

	/**
	 * Get value for number field.
	 *
	 * @return string
	 */
	private function number(): string {
		$value = get_field( $this->field_name, $this->post_id );
		return is_numeric( $value ) ? strval( $value ) : '';
	}

	/**
	 * Get value for radio field.
	 *
	 * @return string
	 */
	private function radio(): string {
		$value = get_field( $this->field_name, $this->post_id );
		return esc_html( $value ? $value : '' );
	}

	/**
	 * Get value for true/false field.
	 *
	 * @return bool
	 */
	private function true_false(): bool {
		$value = get_field( $this->field_name, $this->post_id );
		return (bool) $value;
	}

	/**
	 * Get value for post object field.
	 *
	 * @return array
	 */
	private function post_object(): array {
		$value = get_field( $this->field_name, $this->post_id );
		if ( is_array( $value ) ) {
			return array_map(
				function( $post ) {
					return array(
						'id'    => $post->ID,
						'title' => esc_html( get_the_title( $post ) ),
					);
				},
				$value
			);
		}
		return $value ? array( 'id' => $value->ID, 'title' => esc_html( get_the_title( $value ) ) ) : array();
	}

	/**
	 * Get value for relationship field.
	 *
	 * @return array
	 */
	private function relationship(): array {
		$value = get_field( $this->field_name, $this->post_id );
		return is_array( $value ) ? array_map(
			function( $post ) {
				return array(
					'id'    => $post->ID,
					'title' => esc_html( get_the_title( $post ) ),
				);
			},
			$value
		) : array();
	}

	/**
	 * Get value for taxonomy field.
	 *
	 * @return array
	 */
	private function taxonomy(): array {
		$value = get_field( $this->field_name, $this->post_id );
		if ( is_array( $value ) ) {
			return array_map(
				function( $term ) {
					return array(
						'id'   => $term->term_id,
						'name' => esc_html( $term->name ),
					);
				},
				$value
			);
		}
		return $value ? array( 'id' => $value->term_id, 'name' => esc_html( $value->name ) ) : array();
	}

	/**
	 * Get value for date picker field.
	 *
	 * @return string
	 */
	private function date_picker(): string {
		$value = get_field( $this->field_name, $this->post_id );
		return esc_html( $value ? $value : '' );
	}

	/**
	 * Default fallback for unsupported field types.
	 *
	 * @return string
	 */
	private function default(): string {
		$value = get_field( $this->field_name, $this->post_id );
		return is_string( $value ) ? esc_html( $value ) : '';
	}

	/**
	 * Get the formatted value for the field type.
	 *
	 * @return mixed
	 */
	public function get_value() {
		$method = method_exists( $this, $this->field_type ) ? $this->field_type : 'default';
		return $this->$method();
	}
}