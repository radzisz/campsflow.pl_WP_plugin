<?php
declare(strict_types=1);

namespace Campsflow\Tests\Unit\Widget;

use Brain\Monkey;
use Campsflow\Widget\FieldSorter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FieldSorterTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	#[Test]
	public function sort_returns_visible_fields_in_priority_order(): void {
		$sorter    = new FieldSorter();
		$fieldDefs = [
			[ 'id' => 'alpha', 'label' => 'Alpha' ],
			[ 'id' => 'beta',  'label' => 'Beta' ],
			[ 'id' => 'gamma', 'label' => 'Gamma' ],
		];
		$settings = [
			'show_alpha'    => 'yes',
			'alpha_priority' => 30,
			'show_beta'     => 'yes',
			'beta_priority'  => 10,
			'show_gamma'    => 'yes',
			'gamma_priority' => 20,
		];

		$result = $sorter->sort( $fieldDefs, $settings );

		$this->assertCount( 3, $result );
		$this->assertSame( 'beta',  $result[0]->id );
		$this->assertSame( 'gamma', $result[1]->id );
		$this->assertSame( 'alpha', $result[2]->id );
	}

	#[Test]
	public function hidden_fields_are_excluded(): void {
		$sorter    = new FieldSorter();
		$fieldDefs = [
			[ 'id' => 'alpha', 'label' => 'Alpha' ],
			[ 'id' => 'beta',  'label' => 'Beta' ],
			[ 'id' => 'gamma', 'label' => 'Gamma' ],
		];
		$settings = [
			'show_alpha'    => 'yes',
			'alpha_priority' => 10,
			'show_beta'     => '',
			'beta_priority'  => 20,
			'show_gamma'    => 'yes',
			'gamma_priority' => 30,
		];

		$result = $sorter->sort( $fieldDefs, $settings );

		$this->assertCount( 2, $result );
		$this->assertSame( 'alpha', $result[0]->id );
		$this->assertSame( 'gamma', $result[1]->id );
	}

	#[Test]
	public function all_hidden_returns_empty_array(): void {
		$sorter    = new FieldSorter();
		$fieldDefs = [
			[ 'id' => 'alpha', 'label' => 'Alpha' ],
			[ 'id' => 'beta',  'label' => 'Beta' ],
		];
		$settings = [
			'show_alpha' => '',
			'show_beta'  => '',
		];

		$result = $sorter->sort( $fieldDefs, $settings );

		$this->assertSame( [], $result );
	}

	#[Test]
	public function equal_priority_preserves_fieldDefs_input_order(): void {
		$sorter    = new FieldSorter();
		$fieldDefs = [
			[ 'id' => 'alpha', 'label' => 'Alpha' ],
			[ 'id' => 'beta',  'label' => 'Beta' ],
		];
		$settings = [
			'show_alpha'    => 'yes',
			'alpha_priority' => 10,
			'show_beta'     => 'yes',
			'beta_priority'  => 10,
		];

		$result = $sorter->sort( $fieldDefs, $settings );

		$this->assertCount( 2, $result );
		$this->assertSame( 'alpha', $result[0]->id );
		$this->assertSame( 'beta',  $result[1]->id );
	}
}
