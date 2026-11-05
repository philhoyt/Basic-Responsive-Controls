<?php
/**
 * Tests for the pure migration transform in includes/migrate.php.
 *
 * @package ph-basic-responsive-controls
 */

use PHPUnit\Framework\TestCase;

/**
 * Covers ph_brc_migrate_block(), ph_brc_migrate_blocks() and
 * ph_brc_convert_font_size_value().
 */
final class MigrateBlockTest extends TestCase {

	/**
	 * Registered preset slugs used across tests.
	 *
	 * @var string[]
	 */
	private const SLUGS = array( 'small', 'medium', 'large' );

	/**
	 * Build a parsed heading block with the given attributes.
	 *
	 * @param array  $attrs Block attributes.
	 * @param string $name  Block name.
	 * @return array Parsed block structure.
	 */
	private function block( array $attrs, string $name = 'core/heading' ): array {
		return array(
			'blockName'    => $name,
			'attrs'        => $attrs,
			'innerBlocks'  => array(),
			'innerHTML'    => '<h2>Hello</h2>',
			'innerContent' => array( '<h2>Hello</h2>' ),
		);
	}

	/**
	 * Run the transform on a single block and return its migrated attrs.
	 *
	 * @param array $attrs Input attributes.
	 * @return array Migrated attributes.
	 */
	private function migrate_attrs( array $attrs ): array {
		$result = ph_brc_migrate_block( $this->block( $attrs ), self::SLUGS );
		return $result['block']['attrs'];
	}

	/**
	 * Preset slug becomes var preset.
	 */
	public function test_preset_slug_becomes_var_preset(): void {
		$attrs = $this->migrate_attrs( array( 'tabletFontSize' => 'large' ) );

		$this->assertSame( 'var:preset|font-size|large', $attrs['style']['@tablet']['typography']['fontSize'] );
	}

	/**
	 * Existing var preset is not double converted.
	 */
	public function test_existing_var_preset_is_not_double_converted(): void {
		$attrs = $this->migrate_attrs( array( 'mobileFontSize' => 'var:preset|font-size|large' ) );

		$this->assertSame( 'var:preset|font-size|large', $attrs['style']['@mobile']['typography']['fontSize'] );
	}

	/**
	 * Raw css values are copied verbatim.
	 */
	public function test_raw_css_values_are_copied_verbatim(): void {
		$this->assertSame( 'clamp(1rem, 2vw, 2rem)', ph_brc_convert_font_size_value( 'clamp(1rem, 2vw, 2rem)', self::SLUGS ) );
		$this->assertSame( '1.2rem', ph_brc_convert_font_size_value( '1.2rem', self::SLUGS ) );
		$this->assertSame( 'var(--custom)', ph_brc_convert_font_size_value( 'var(--custom)', self::SLUGS ) );
		$this->assertSame( '18', ph_brc_convert_font_size_value( '18', self::SLUGS ) );
	}

	/**
	 * Tablet only value cascades to mobile.
	 */
	public function test_tablet_only_value_cascades_to_mobile(): void {
		$attrs = $this->migrate_attrs( array( 'tabletLineHeight' => '1.2' ) );

		$this->assertSame( '1.2', $attrs['style']['@tablet']['typography']['lineHeight'] );
		$this->assertSame( '1.2', $attrs['style']['@mobile']['typography']['lineHeight'] );
	}

	/**
	 * Tablet and mobile values do not cascade.
	 */
	public function test_tablet_and_mobile_values_do_not_cascade(): void {
		$attrs = $this->migrate_attrs(
			array(
				'tabletLetterSpacing' => '1px',
				'mobileLetterSpacing' => '2px',
			)
		);

		$this->assertSame( '1px', $attrs['style']['@tablet']['typography']['letterSpacing'] );
		$this->assertSame( '2px', $attrs['style']['@mobile']['typography']['letterSpacing'] );
	}

	/**
	 * Existing core tablet value wins over legacy tablet.
	 */
	public function test_existing_core_tablet_value_wins_over_legacy_tablet(): void {
		$attrs = $this->migrate_attrs(
			array(
				'tabletTextAlign' => 'left',
				'style'           => array( '@tablet' => array( 'typography' => array( 'textAlign' => 'right' ) ) ),
			)
		);

		$this->assertSame( 'right', $attrs['style']['@tablet']['typography']['textAlign'] );
		// Mobile had no core value, so the legacy tablet value still cascades.
		$this->assertSame( 'left', $attrs['style']['@mobile']['typography']['textAlign'] );
	}

	/**
	 * Existing core mobile value wins and tablet takes legacy.
	 */
	public function test_existing_core_mobile_value_wins_and_tablet_takes_legacy(): void {
		$attrs = $this->migrate_attrs(
			array(
				'tabletFontSize' => 'small',
				'style'          => array( '@mobile' => array( 'typography' => array( 'fontSize' => '12px' ) ) ),
			)
		);

		$this->assertSame( 'var:preset|font-size|small', $attrs['style']['@tablet']['typography']['fontSize'] );
		$this->assertSame( '12px', $attrs['style']['@mobile']['typography']['fontSize'] );
	}

	/**
	 * Existing core tablet with legacy mobile only writes mobile.
	 */
	public function test_existing_core_tablet_with_legacy_mobile_only_writes_mobile(): void {
		$attrs = $this->migrate_attrs(
			array(
				'mobileLineHeight' => '1.1',
				'style'            => array( '@tablet' => array( 'typography' => array( 'lineHeight' => '1.6' ) ) ),
			)
		);

		$this->assertSame( '1.6', $attrs['style']['@tablet']['typography']['lineHeight'] );
		$this->assertSame( '1.1', $attrs['style']['@mobile']['typography']['lineHeight'] );
	}

	/**
	 * Base style is preserved alongside states.
	 */
	public function test_base_style_is_preserved_alongside_states(): void {
		$attrs = $this->migrate_attrs(
			array(
				'tabletFontSize' => 'medium',
				'style'          => array( 'typography' => array( 'fontSize' => '2rem' ) ),
			)
		);

		$this->assertSame( '2rem', $attrs['style']['typography']['fontSize'] );
		$this->assertSame( 'var:preset|font-size|medium', $attrs['style']['@tablet']['typography']['fontSize'] );
	}

	/**
	 * Legacy keys and block id are removed.
	 */
	public function test_legacy_keys_and_block_id_are_removed(): void {
		$attrs = $this->migrate_attrs(
			array(
				'blockId'             => 'abc123',
				'tabletFontSize'      => 'large',
				'mobileFontSize'      => '',
				'tabletLineHeight'    => '',
				'mobileLineHeight'    => '',
				'tabletTextAlign'     => 'center',
				'mobileTextAlign'     => '',
				'tabletLetterSpacing' => '',
				'mobileLetterSpacing' => '',
				'level'               => 2,
			)
		);

		foreach ( ph_brc_legacy_attribute_keys() as $key ) {
			$this->assertArrayNotHasKey( $key, $attrs, "$key should be removed" );
		}
		$this->assertArrayNotHasKey( 'blockId', $attrs );
		$this->assertSame( 2, $attrs['level'], 'Unrelated attributes are untouched' );
	}

	/**
	 * Nested heading inside group is migrated.
	 */
	public function test_nested_heading_inside_group_is_migrated(): void {
		$group = array(
			'blockName'    => 'core/group',
			'attrs'        => array( 'tabletFontSize' => 'large' ),
			'innerBlocks'  => array(
				$this->block( array( 'tabletFontSize' => 'large' ) ),
				$this->block( array( 'mobileTextAlign' => 'center' ), 'core/paragraph' ),
			),
			'innerHTML'    => '',
			'innerContent' => array( null, null ),
		);

		$result = ph_brc_migrate_blocks( array( $group ), self::SLUGS );

		$this->assertSame( 2, $result['changed'] );
		$this->assertSame( 'large', $result['blocks'][0]['attrs']['tabletFontSize'], 'Group is not a migratable block' );
		$this->assertSame(
			'var:preset|font-size|large',
			$result['blocks'][0]['innerBlocks'][0]['attrs']['style']['@tablet']['typography']['fontSize']
		);
		$this->assertSame(
			'center',
			$result['blocks'][0]['innerBlocks'][1]['attrs']['style']['@mobile']['typography']['textAlign']
		);
	}

	/**
	 * Unrelated block is untouched.
	 */
	public function test_unrelated_block_is_untouched(): void {
		$image  = $this->block( array( 'tabletFontSize' => 'large' ), 'core/image' );
		$result = ph_brc_migrate_block( $image, self::SLUGS );

		$this->assertSame( 0, $result['changed'] );
		$this->assertSame( $image, $result['block'] );
	}

	/**
	 * Block with only empty legacy values has the keys stripped and no styles written.
	 */
	public function test_block_with_only_empty_legacy_values_has_keys_stripped(): void {
		$heading = $this->block(
			array(
				'blockId'        => 'abc',
				'tabletFontSize' => '',
				'mobileFontSize' => '   ',
				'level'          => 3,
			)
		);
		$result  = ph_brc_migrate_block( $heading, self::SLUGS );

		$this->assertSame( 1, $result['changed'] );
		$this->assertSame( array( 'level' => 3 ), $result['block']['attrs'] );
	}

	/**
	 * Block carrying only a blockId is left untouched.
	 */
	public function test_block_with_only_block_id_is_unchanged(): void {
		$heading = $this->block( array( 'blockId' => 'abc' ) );
		$result  = ph_brc_migrate_block( $heading, self::SLUGS );

		$this->assertSame( 0, $result['changed'] );
		$this->assertSame( $heading, $result['block'] );
	}

	/**
	 * Freeform block without name is skipped.
	 */
	public function test_freeform_block_without_name_is_skipped(): void {
		$freeform = array(
			'blockName'    => null,
			'attrs'        => array(),
			'innerBlocks'  => array(),
			'innerHTML'    => "\n\n",
			'innerContent' => array( "\n\n" ),
		);
		$result   = ph_brc_migrate_blocks( array( $freeform ), self::SLUGS );

		$this->assertSame( 0, $result['changed'] );
		$this->assertSame( array( $freeform ), $result['blocks'] );
	}
}
