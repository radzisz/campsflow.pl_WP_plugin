<?php
declare(strict_types=1);

namespace Campsflow\Widget;

final class FieldConfig {
	public function __construct(
		public readonly string $id,
		public readonly string $label,
		public readonly bool $visible,
		public readonly int $priority,
		public readonly ?string $lockedValue,
		public readonly ?string $defaultValue,
	) {}
}
