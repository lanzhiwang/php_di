<?php
class Bim
{
    public function doSomething()
    {
        echo __METHOD__, '|';
    }
}

class Bar
{
    private $bim;

    public function __construct(Bim $bim)
    {
        $this->bim = $bim;
    }

    public function doSomething()
    {
        $this->bim->doSomething();
        echo __METHOD__, '|';
    }
}

class Foo
{
    private $bar;

    public function __construct(Bar $bar)
    {
        $this->bar = $bar;
    }

    public function doSomething()
    {
        $this->bar->doSomething();
        echo __METHOD__;
    }
}

class IoC
{
    protected static $registry = [];

    public static function bind($name, Callable $resolver)
    {
        static::$registry[$name] = $resolver;
    }

    public static function make($name)
    {
        if (isset(static::$registry[$name])) {
            $resolver = static::$registry[$name];
            return $resolver();
        }
        throw new Exception('Alias does not exist in the IoC registry.');
    }
}

IoC::bind('bim', function () {
    return new Bim();
});

IoC::bind('bar', function () {
    return new Bar(IoC::make('bim'));
});

IoC::bind('foo', function () {
    return new Foo(IoC::make('bar'));
});


// 从容器中取得Foo
$foo = IoC::make('foo');
$foo->doSomething(); // Bim::doSomething|Bar::doSomething|Foo::doSomething
echo "\n";
