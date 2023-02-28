<?php
namespace Jeht\Support\Traits;

trait InheritanceAware
{
	/**
	 * Returns a list of parents of the given $object, filtered by the
	 * $mandatoryRoot class or interface name.
	 *
	 * @param string|object $object
	 * @param string $mandatoryRoot
	 * @return array
	 */
	protected function filteredParents($object, string $mandatoryRoot)
	{
		if ($parents = class_parents($object)) {
			return array_filter($parents, function ($item) use ($mandatoryRoot) {
				return is_a($item, $mandatoryRoot, true);
			});
		}
		//
		return [];
	}

	/**
	 * Returns a list of the implemented interfaces for the given $object,
	 * filtered by the $mandatoryRoot interface name.
	 *
	 * @param string|object $object
	 * @param string $mandatoryRoot
	 * @return array
	 */
	protected function filteredInterfaces($object, string $mandatoryRoot)
	{
		if ($interfaces = class_implements($object)) {
			return array_filter($interfaces, function ($item) use ($mandatoryRoot) {
				return is_a($item, $mandatoryRoot, true);
			});
		}
		//
		return [];
	}
}
