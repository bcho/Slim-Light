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
        if (is_null($route) || empty(array_filter($route))) {
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
}
