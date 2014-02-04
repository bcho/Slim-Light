<?php

require_once 'bootstrap.php';


class RouteMapTest extends Light_TestCase
{
    /**
     * @dataProvider routePatternProvider
     */
    public function testRoutePattern($pattern, $expected)
    {
        $name = 'test';
        $methods = 'GET';
        $callable = function () {};

        $app = $this->getDefaultAPP();
        $rv = $app->set($name, $callable);
        $this->assertSame($app, $rv);
        $rv = $app->route($name, $pattern, $methods);
        $this->assertEquals($expected, $rv->getPattern());

        $app = $this->getDefaultAPP();
        $rv = $app->route($name, $pattern ,$methods);
        $this->assertSame($app, $rv);
        $rv = $app->set($name, $callable);
        $this->assertEquals($expected, $rv->getPattern());

        // group test
        $app->group('/test', function () use ($app, $name, $pattern, $methods, $callable, $expected) {
        });
    }

    /**
     * @dataProvider routePatternProvider
     */
    public function testGroupRoutePattern($pattern, $expected)
    {
        $prefix = '/test';

        $app = $this->getDefaultAPP();
        $app->group($prefix, function () use ($app, $pattern, $expected, $prefix) {
            $name = 'test';
            $methods = 'GET';
            $callable = function () {};
            
            $rv = $app->route($name, $pattern, $methods);
            $this->assertSame($app, $rv);
            $rv = $app->set($name, $callable);
            $this->assertEquals($prefix . $expected, $rv->getPattern());
        });
    }

    /**
     * @dataProvider routeMethodsProvider
     */
    public function testRouteMethods($methods, $expected)
    {
        $name = 'test';
        $pattern = '/test';
        $callable = function () {};

        $app = $this->getDefaultAPP();
        $app->route($name, $pattern, $methods);
        $rv = $app->set($name, $callable);
        $this->assertEquals($expected, $rv->getHttpMethods());
    }

    public function testIntConverter()
    {
        $callable = function () {};
        $name = 'test';
        $methods = 'GET';

        $app = $this->getDefaultAPP();
        $app->set($name, $callable);
        $rv = $app->route($name, '/int:id', $methods);

        $this->assertTrue($rv->matches('/1'));
        $this->assertTrue($rv->matches('/23'));
        $this->assertTrue($rv->matches('/234'));
        $this->assertTrue($rv->matches('/2345'));
        $this->assertTrue($rv->matches('/02345'));
        $this->assertFalse($rv->matches('/abc'));
        $this->assertFalse($rv->matches('/123abc'));
        $this->assertFalse($rv->matches('/a123abc'));
        $this->assertFalse($rv->matches('abc/a123abc'));
    }

    public function testStrConverter()
    {
        $callable = function() {};
        $name = 'test';
        $methods = 'GET';

        $app = $this->getDefaultAPP();
        $app->set($name, $callable);

        $rv = $app->route($name, '/:name', $methods);
        $this->assertTrue($rv->matches('/1'));
        $this->assertTrue($rv->matches('/123'));
        $this->assertTrue($rv->matches('/name'));
        $this->assertTrue($rv->matches('/larry'));
        $this->assertTrue($rv->matches('/foobar'));
        $this->assertTrue($rv->matches('/orz'));
        $this->assertFalse($rv->matches('/abc/orz'));

        $rv = $app->route($name, '/str:name', $methods);
        $this->assertTrue($rv->matches('/1'));
        $this->assertTrue($rv->matches('/123'));
        $this->assertTrue($rv->matches('/name'));
        $this->assertTrue($rv->matches('/larry'));
        $this->assertTrue($rv->matches('/foobar'));
        $this->assertTrue($rv->matches('/orz'));
        $this->assertFalse($rv->matches('/abc/orz'));
    }

    public function routePatternProvider()
    {
        return array(
            array('/', '/'),
            array('/foo', '/foo'),
            array('/foo/', '/foo/'),
            array('/foo/bar', '/foo/bar'),
            array('/:id', '/:id'),
            array('/foo/:id', '/foo/:id'),
            array('/foo/int:id', '/foo/:id'),
            array('/:id/foo', '/:id/foo'),
            array('/foo/:id1/:id2', '/foo/:id1/:id2'),
            array('/foo/int:id1/str:id2', '/foo/:id1/:id2'),
            array('/bar:id', '/bar:id')
        );
    }

    public function routeMethodsProvider()
    {
        return array(
            array(array('GET'), array('GET')),
            array(array('POST'), array('POST')),
            array(array('PUT'), array('PUT')),
            array(array('DELETE'), array('DELETE')),
            array(array('OPTION'), array('OPTION')),
            array(array('HEAD'), array('HEAD')),

            array(array('GET', 'POST'), array('GET', 'POST')),
            array(array('GET', 'POST', 'PUT'), array('GET', 'POST', 'PUT'))
        );
    }
}
