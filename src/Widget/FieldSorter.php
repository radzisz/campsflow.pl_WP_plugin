<?php
declare(strict_types=1);

namespace Campsflow\Widget;

final class FieldSorter {
	/**
	 * Returns visible fields from $fieldDefs sorted by priority ascending.
	 *
	 * Settings-key convention (derived from $def['id']):
	 *   visible  → show_{id}         ('yes' = visible, anything else = hidden)
	 *   priority → {id}_priority     (int; defaults to 50 when absent)
	 *   locked   → {id}_locked       (?string; null when empty)
	 *   default  → {id}_default      (?string; null when empty)
	 *
	 * PHP 8.0+ guarantees usort() is stable, so equal-priority fields
	 * preserve their $fieldDefs input order automatically.
	 *
	 * @param list<array{id: string, label: string}> $fieldDefs
	 * @param array<string, mixed>                   $settings
	 * @return list<FieldConfig>
	 */
	public function sort( array $fieldDefs, array $settings ): array {
		$visible = array();
		foreach ( $fieldDefs as $def ) {
			$id = (string) $def['id'];
			if ( ( $settings[ "show_{$id}" ] ?? '' ) !== 'yes' ) {
				continue;
			}
			$priority     = (int) ( $settings[ "{$id}_priority" ] ?? 50 );
			$rawLocked    = $settings[ "{$id}_locked" ] ?? '';
			$rawDefault   = $settings[ "{$id}_default" ] ?? '';
			$lockedValue  = is_string( $rawLocked ) && $rawLocked !== '' ? $rawLocked : null;
			$defaultValue = is_string( $rawDefault ) && $rawDefault !== '' ? $rawDefault : null;
			$visible[]    = new FieldConfig( $id, (string) $def['label'], true, $priority, $lockedValue, $defaultValue );
		}
		usort( $visible, static fn ( FieldConfig $a, FieldConfig $b ): int => $a->priority <=> $b->priority );
		return array_values( $visible );
	}
}
