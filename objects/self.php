	<?php

	class A {

		public static function newStaticClass()
		{
			return new static;
		}

		public static function newSelfClass()
		{
			return new self;
		}

		public function newThisClass()
		{
			return new $this;
		}
	}

	class B extends A
	{
		public function newParentClass()
		{
			return new parent;
		}
	}


	$b = new B;

	var_dump($b::newStaticClass()); // B
	var_dump($b::newSelfClass()); // A because self belongs to "A"
	var_dump($b->newThisClass()); // B
	var_dump($b->newParentClass()); // A


	class C extends B
	{
		public static function newSelfClass()
		{
			return new self;
		}
	}


	$c = new C;

	var_dump($c::newStaticClass()); // C
	var_dump($c::newSelfClass()); // C because self now points to "C" class
	var_dump($c->newThisClass()); // C
	var_dump($b->newParentClass()); // A because parent was defined *way back* in class "B"
