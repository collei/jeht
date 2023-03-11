<?php
namespace Jeht\Container;

use Closure;
use Jeht\Support\Arr;
use Jeht\Container\Interfaces\Container;
use Jeht\Container\Interfaces\ContextualBindingBuilder as ContextualBindingBuilderContract;

class ContextualBindingBuilder implements ContextualBindingBuilderContract
{
	/**
	 * The underlying container instance.
	 *
	 * @var \Jeht\Container\Interfaces\Container
	 */
	protected $container;

	/**
	 * The concrete instance.
	 *
	 * @var string|array
	 */
	protected $concrete;

	/**
	 * The abstract target.
	 *
	 * @var string
	 */
	protected $needs;

	/**
	 * Create a new contextual binding builder.
	 *
	 * @param  \Jeht\Container\Interfaces\Container  $container
	 * @param  string|array  $concrete
	 * @return void
	 */
	public function __construct(Container $container, $concrete)
	{
		$this->concrete = $concrete;
		$this->container = $container;
	}

	/**
	 * Define the abstract target that depends on the context.
	 *
	 * @param  string  $abstract
	 * @return $this
	 */
	public function needs($abstract)
	{
		$this->needs = $abstract;
		//
		return $this;
	}

	/**
	 * Define the implementation for the contextual binding.
	 *
	 * @param  \Closure|string  $implementation
	 * @return void
	 */
	public function give($implementation)
	{
		foreach (Arr::wrap($this->concrete) as $concrete) {
			$this->container->addContextualBinding(
				$concrete,
				$this->needs,
				$implementation
			);
		}
	}
}

