<?php
namespace Jeht\Debug;

class VarDumper
{
	public static dump()
	{
		$args = func_get_args();
		//
		print_r($args);
	}

}
