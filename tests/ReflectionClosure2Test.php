<?php
/* ===========================================================================
 * Copyright (c) 2018 Zindex Software
 *
 * Licensed under the MIT License
 * =========================================================================== */

namespace Opis\Closure\Test;

use Closure;
use Opis\Closure\ReflectionClosure;

// Fake
use Foo\{Bar, Baz as Qux};
use function Foo\f1;
use function Bar\{b1, b2 as b3};

class ReflectionClosure2Test extends \PHPUnit\Framework\TestCase
{
    protected function c(Closure $closure)
    {
        $r = new ReflectionClosure($closure);
        return $r->getCode();
    }

    public function testResolveArguments()
    {
        $f1 = function (Bar $p){};
        $e1 = 'function (\Foo\Bar $p){}';

        $f2 = function (Bar\Test $p){};
        $e2 = 'function (\Foo\Bar\Test $p){}';

        $f3 = function (Qux $p){};
        $e3 = 'function (\Foo\Baz $p){}';

        $f4 = function (Qux\Test $p){};
        $e4 = 'function (\Foo\Baz\Test $p){}';

        $f5 = function (array $p, string $x){};
        $e5 = 'function (array $p, string $x){}';


        $this->assertEquals($e1, $this->c($f1));
        $this->assertEquals($e2, $this->c($f2));
        $this->assertEquals($e3, $this->c($f3));
        $this->assertEquals($e4, $this->c($f4));
        $this->assertEquals($e5, $this->c($f5));
    }

    public function testResolveReturnType()
    {
        $f1 = function (): Bar{};
        $e1 = 'function (): \Foo\Bar{}';

        $f2 = function (): Bar\Test{};
        $e2 = 'function (): \Foo\Bar\Test{}';

        $f3 = function (): Qux{};
        $e3 = 'function (): \Foo\Baz{}';

        $f4 = function (): Qux\Test{};
        $e4 = 'function (): \Foo\Baz\Test{}';

        $f5 = function (): \Foo{};
        $e5 = 'function (): \Foo{}';

        $f6 = function (): Foo{};
        $e6 = 'function (): \\' . __NAMESPACE__. '\Foo{}';

        $f7 = function (): array{};
        $e7 = 'function (): array{}';

        $f8 = function (): string{};
        $e8 = 'function (): string{}';

        $this->assertEquals($e1, $this->c($f1));
        $this->assertEquals($e2, $this->c($f2));
        $this->assertEquals($e3, $this->c($f3));
        $this->assertEquals($e4, $this->c($f4));
        $this->assertEquals($e5, $this->c($f5));
        $this->assertEquals($e6, $this->c($f6));
        $this->assertEquals($e7, $this->c($f7));
        $this->assertEquals($e8, $this->c($f8));
    }

    public function testClosureInsideClosure()
    {
        $f1 = function() { return function ($a): A { return $a; }; };
        $e1 = 'function() { return function ($a): \Opis\Closure\Test\A { return $a; }; }';


        $f2 = function() { return function (A $a): A { return $a; }; };
        $e2 = 'function() { return function (\Opis\Closure\Test\A $a): \Opis\Closure\Test\A { return $a; }; }';

        $this->assertEquals($e1, $this->c($f1));
        $this->assertEquals($e2, $this->c($f2));
    }

    public function testAnonymousInsideClosure()
    {
        $f1 = function() { return new class extends A {}; };
        $e1 = 'function() { return new class extends \Opis\Closure\Test\A {}; }';

        $f2 = function() { return new class extends A implements B {}; };
        $e2 = 'function() { return new class extends \Opis\Closure\Test\A implements \Opis\Closure\Test\B {}; }';

        $f3 = function() { return new class { function x(A $a): B {} }; };
        $e3 = 'function() { return new class { function x(\Opis\Closure\Test\A $a): \Opis\Closure\Test\B {} }; }';

        $this->assertEquals($e1, $this->c($f1));
        $this->assertEquals($e2, $this->c($f2));
        $this->assertEquals($e3, $this->c($f3));
    }

    public function testClosureResolveTraitsNamesInAnonymousClasses()
    {
        $f1 = function () { new class { use Bar; }; };
        $e1 = 'function () { new class { use \Foo\Bar; }; }';

        $f2 = function () { new class { use Bar\Test; }; };
        $e2 = 'function () { new class { use \Foo\Bar\Test; }; }';

        $f3 = function () { new class { use Qux; }; };
        $e3 = 'function () { new class { use \Foo\Baz; }; }';

        $f4 = function () { new class { use Qux\Test; }; };
        $e4 = 'function () { new class { use \Foo\Baz\Test; }; }';

        $f5 = function () { new class { use \Foo; }; };
        $e5 = 'function () { new class { use \Foo; }; }';

        $f6 = function () { new class { use Foo; }; };
        $e6 = 'function () { new class { use \\' . __NAMESPACE__ . '\Foo; }; }';

        $f7 = function () { new class { use Bar; }; function a(Qux $q): Bar { f1(); $a = new class extends Bar {}; } };
        $e7 = 'function () { new class { use \Foo\Bar; }; function a(\Foo\Baz $q): \Foo\Bar '
            . '{ \Foo\f1(); $a = new class extends \Foo\Bar {}; } }';

        $this->assertEquals($e1, $this->c($f1));
        $this->assertEquals($e2, $this->c($f2));
        $this->assertEquals($e3, $this->c($f3));
        $this->assertEquals($e4, $this->c($f4));
        $this->assertEquals($e5, $this->c($f5));
        $this->assertEquals($e6, $this->c($f6));
        $this->assertEquals($e7, $this->c($f7));
    }

    public function testStaticMethodInstanceOfUsage()
    {
        $f1 = function () { Bar::instanceOf(); };
        $e1 = 'function () { \Foo\Bar::instanceOf(); }';

        $f2 = function () { $any instanceof Bar; };
        $e2 = 'function () { $any instanceof \Foo\Bar; }';

        $this->assertEquals($e1, $this->c($f1));
        $this->assertEquals($e2, $this->c($f2));
    }

    public function testThisInsideAnonymousClass()
    {
        $f1 = function() {
            return new class {
                function a(){
                    $self = $this;
                }
            };
        };

        $f2 = function () {
            return new class {
                function a(){
                    $self = $this;
                    return new class {
                        function a(){
                            $self = $this;
                        }
                    };
                }
            };
        };

        $f3 = function () {
            $self = $this;
            return new class {
                function a(){
                    $self = $this;
                }
            };
        };

        $f4 = function () {
            return new class {
                function a(){
                    $self = $this;
                }
            };
            $self = $this;
        };

        $this->assertFalse((new ReflectionClosure($f1))->isBindingRequired());
        $this->assertFalse((new ReflectionClosure($f2))->isBindingRequired());
        $this->assertTrue((new ReflectionClosure($f3))->isBindingRequired());
        $this->assertTrue((new ReflectionClosure($f4))->isBindingRequired());
    }
}