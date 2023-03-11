<?php
namespace Jeht\Support\Interfaces;

/**
 * Adapted from Laravel's Illuminate\Contracts\Support\Responsable
 * @link https://laravel.com/api/8.x/Illuminate/Contracts/Support/Responsable.html
 * @link https://github.com/laravel/framework/blob/8.x/src/Illuminate/Contracts/Support/Responsable.php
 *
 */
interface Responsable
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Jeht\Http\Request  $request
     * @return \Jeht\Http\Response
     */
    public function toResponse($request);
}

