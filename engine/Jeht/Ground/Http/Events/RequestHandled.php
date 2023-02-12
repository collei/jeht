<?php
namespace Jeht\Ground\Http\Events;

/**
 * Adapted from Laravel's Illuminate\Foundation\Http\Events\RequestHandled
 * @link https://laravel.com/api/8.x/Illuminate/Foundation/Http/Events/RequestHandled.html
 * @link https://github.com/laravel/framework/blob/8.x/src/Illuminate/Foundation/Http/Events/RequestHandled.php
 */
class RequestHandled
{
	/**
	 * The request instance.
	 *
	 * @var \Jeht\Http\Request
	 */
	public $request;

	/**
	 * The response instance.
	 *
	 * @var \Jeht\Http\Response
	 */
	public $response;

	/**
	 * Create a new event instance.
	 *
	 * @param  \Jeht\Http\Request  $request
	 * @param  \Jeht\Http\Response  $response
	 * @return void
	 */
	public function __construct($request, $response)
	{
		$this->request = $request;
		$this->response = $response;
	}
}

