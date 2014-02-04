<?php

require_once 'bootstrap.php';


class MovieResource extends \Slim\Light\ResourceController
{
    public function get($id) {
        echo $id;
    }

    public function update($id) {
        echo $id;
    }

    public function remove($id) {
        echo $id;
    }

    public function get_all() {
        echo 'All movies.';
    }

    public function create() {
        echo 'Create a movie.';
    }

    public function get_by_id($id) {
        echo $id;
    }

    public function edit_by_id($id) {
        echo "Edit movie $id.";
    }
}


class ResourceControllerTest extends Light_TestCase
{
    public function setup()
    {
        $this->app = $this->getDefaultAPP();
    }

    protected function request($method, $path, $options = array())
    {
        ob_start();

        \Slim\Environment::mock(array_merge(array(
            'REQUEST_METHOD' => $method,
            'PATH_INFO'      => $path,
            'SERVER_NAME'    => 'local.dev',
        ), $options));

        $this->request = $this->app->request();
        $this->response = $this->app->response();

        $this->app->run();

        return ob_get_clean();
    }

    public function get($path, $options = array())
    {
        return $this->request('GET', $path, $options);
    }

    public function post($path, $options = array(), $postVars = array())
    {
        $options['slim.input'] = http_build_query($postVars);
        return $this->request('POST', $path, $options);
    }

    public function patch($path, $options = array(), $postVars = array())
    {
        $options['slim.input'] = http_build_query($postVars);
        return $this->request('PATCH', $path, $options);
    }
    
    public function put($path, $options = array(), $postVars = array())
    {
        $options['slim.input'] = http_build_query($postVars);
        return $this->request('PUT', $path, $options);
    }
    
    public function delete($path, $options = array())
    {
        return $this->request('DELETE', $path, $options);
    }

    public function head($path, $options = array())
    {
        return $this->request('HEAD', $path, $options);
    }

    /**
     * @dataProvider routeProvider
     */
    public function testRoute($method, $pattern, $expected, $options = array())
    {
        $name = 'movie';
        $prefix = "/$name";
        $this->app->resource($name, $prefix, new MovieResource());

        $rv = $this->request($method, "$prefix$pattern", $options);
        $this->assertEquals($expected, $rv);
    }

    public function routeProvider()
    {
        return array(
            array('GET', '/1', '1'),
            array('POST', '/123', '123'),
            array('PUT', '/1', '1'),
            array('DELETE', '/1', '1'),
            array('GET', '', 'All movies.'),
            array('POST', '', 'Create a movie.')
        );
    }
}
