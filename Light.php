<?php

namespace Slim\Light;


class Light extends \Slim\Slim
{
    /**
     * @var array Builtin converters.
     */
    protected $converters = array(
        'str' => '\w+',
        'int' => '\d+'
    );
    
    /**
     * @var string Converter match pattern.
     */
    protected $converterPattern = '/\/(str|int)\:(\w+)/';

    /**
     * @var array
     */
    protected $namedRoutes = array();

    /**
     * @var array
     */
    protected $defaultResourceRoutes = array(
        'get' => array(
            'pattern' => '/int:id',
            'methods' => array('GET')
        ),
        'update' => array(
            'pattern' => '/int:id',
            'methods' => array('POST', 'PUT')
        ),
        'remove' => array(
            'pattern' => '/int:id',
            'methods' => array('DELETE')
        ),
        'get_all' => array(
            'pattern' => '/',
            'methods' => array('GET')
        ),
        'create' => array(
            'pattern' => '/',
            'methods' => array('POST')
        )
    );

    /**
     * Build a named route pair.
     *
     * @return array
     */
    protected function buildNamedRoute()
    {
        return array(
            'name' => null,
            'rules' => null,
            'pattern' => null,
            'methods' => null
        );
    }

    /**
     * Map named route.
     *
     * @param string route's name
     * @return \Slim\Route
     * @throws \RuntimeException if named route's settings is incomplete.
     */
    protected function mapNamedRoute($name)
    {
        $route = $this->namedRoutes[$name];
        if (is_null($route) || count(array_filter($route)) === 0) {
            throw new RuntimeException("Route $name cannot be mapped!");
        }

        // prepare route pattern & param conditions
        $pattern = $route['pattern'];
        $conditions = array();
        $matches = array();
        if (preg_match_all($this->converterPattern, $pattern, $matches)) {
            for ($i = 0;$i < count($matches[1]);$i++) {
                $c = $matches[1][$i];
                $p = $matches[2][$i];
                $pat = "/$c\:$p/";
                $pattern = preg_replace($pat, ":$p", $pattern);
                $conditions[$p] = $this->converters[$c];
            }
        }
        $route['rules'][0] = $pattern;

        $rv = $this->mapRoute($route['rules'])
            ->conditions($conditions)
            ->name($name);

        foreach ($route['methods'] as $method) {
            $rv->via($method);
        }

        return $rv;
    }

    /**
     * Set a callbale and some middlewares to a named route.
     *
     * USAGE:
     *
     *      $app->set('tests'[, 'md1', 'md2'], $callable);
     *
     * If the route had set pattern & methods before
     * (i.e., use `\Slim\Light\Light::route`), it will map the route
     * and return a `\Slim\Route`. Otherwise, it will return itself for 
     * chaining execute.
     *
     * @param array SEE ABOVE
     * @return \Slim\Route | \Slim\Light\Light
     */
    public function set()
    {
        $args = func_get_args();
        $name = $args[0];
        $args[0] = null;

        if (isset($this->namedRoutes[$name])) {
            $this->namedRoutes[$name]['rules'] = $args;
            return $this->mapNamedRoute($name);
        }

        $route = $this->buildNamedRoute();
        $route['name'] = $name;
        $route['rules'] = $args;
        $this->namedRoutes[$name] = $route;

        return $this;
    }

    /**
     * Set a named route's pattern and methods.
     *
     * If the route had set callable before (i.e., use `\Slim\Light\Light::set`),
     * it will return `\Slim\Route`. Otherwise, it will return itself for 
     * chaining execute.
     *
     * @param string route name
     * @param string route pattern
     * @param mixed route methods
     * @return \Slim\Route | \Slim\Light\Light
     */
    public function route($name, $pattern, $methods)
    {
        // support array form and string form
        if (!is_array($methods)) {
            $methods = array($methods);
        }

        if (isset($this->namedRoutes[$name])) {
            $this->namedRoutes[$name]['pattern'] = $pattern;
            $this->namedRoutes[$name]['methods'] = $methods;
            return $this->mapNamedRoute($name);
        }

        $route = $this->buildNamedRoute();
        $route['name'] = $name;
        $route['pattern'] = $pattern;
        $route['methods'] = $methods;
        $this->namedRoutes[$name] = $route;

        return $this;
    }

    /**
     * Register a resource object.
     *
     * Register results as follows:
     *
     *      Method          Route Name          Pattern         Method
     *      get($id)        $res_name@get       $pattern/$id    GET
     *      update($id)     $res_name@update    $pattern/$id    POST / PUT
     *      remove($id)     $res_name@remove    $pattern/$id    DELETE
     *      get_all()       $res_name@get_all   $pattern        GET
     *      create()        $res_name@create    $pattern        POST
     *
     * USAGE:
     *      
     *      $app->resource($res_name, $pattern[, 'md1', 'md2'], new ResourceObj());
     *
     * TODO: Provide customize route mapping.
     *
     * @param array SEE ABOVE
     * @return \Slim\Light\Light
     */
    public function resource()
    {
        // Extract resource name, pattern and resource object.
        $args = func_get_args();
        $name = array_shift($args);
        $pattern = $args[0];
        $res_obj = array_pop($args);

        // Reorder the arguments to suit the `set` method.
        $callable_pos = count($args);
        $args[] = NULL;

        foreach ($this->defaultResourceRoutes as $method => $v) {
            $route_name = "$name@$method";
            $this->route($route_name, $pattern . $v['pattern'], $v['methods']);

            // Reorder the arguments to suit the `set` method.
            $args[0] = $route_name;
            // Create a function clousure which calls resource object's method.
            $args[$callable_pos] = function () use ($res_obj, $method) {
                $args = func_get_args();
                return call_user_method_array($method, $res_obj, $args);
            };
            call_user_method_array('set', $this, $args);
        }

        return $this;
    }
}
