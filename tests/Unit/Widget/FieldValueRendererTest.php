<?php
declare(strict_types=1);

namespace Campsflow\Tests\Unit\Widget;

use Brain\Monkey;
use Campsflow\Widget\FieldValueRenderer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FieldValueRendererTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		// Alias distinguishes which branch was taken even with passthrough values
		Monkey\Functions\when( 'esc_html' )->alias( static fn( string $v ): string => 'ESC:' . $v );
		Monkey\Functions\when( 'wp_kses_post' )->alias( static fn( string $v ): string => 'KSES:' . $v );
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	#[Test]
	public function auto_mode_plain_text_uses_esc_html(): void {
		$renderer = new FieldValueRenderer();
		$result   = $renderer->applyRenderMode( 'plain text', 'auto' );
		$this->assertSame( 'ESC:plain text', $result );
	}

	#[Test]
	public function auto_mode_html_content_uses_wp_kses_post(): void {
		$renderer = new FieldValueRenderer();
		$result   = $renderer->applyRenderMode( '<p>html content</p>', 'auto' );
		$this->assertSame( 'KSES:<p>html content</p>', $result );
	}

	#[Test]
	public function text_mode_always_uses_esc_html_even_for_html_content(): void {
		$renderer = new FieldValueRenderer();
		$result   = $renderer->applyRenderMode( '<p>html</p>', 'text' );
		$this->assertSame( 'ESC:<p>html</p>', $result );
	}

	#[Test]
	public function html_mode_always_uses_wp_kses_post_even_for_plain_text(): void {
		$renderer = new FieldValueRenderer();
		$result   = $renderer->applyRenderMode( 'plain', 'html' );
		$this->assertSame( 'KSES:plain', $result );
	}

	#[Test]
	public function empty_string_returns_empty_in_all_modes(): void {
		$renderer = new FieldValueRenderer();
		$this->assertSame( 'ESC:', $renderer->applyRenderMode( '', 'text' ) );
		$this->assertSame( 'KSES:', $renderer->applyRenderMode( '', 'html' ) );
		$this->assertSame( 'ESC:', $renderer->applyRenderMode( '', 'auto' ) );
	}
}
