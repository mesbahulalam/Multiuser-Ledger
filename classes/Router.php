<?php
class Router {
    private $routes = [];
    private $beforeCallback = [];
    private $prefix = '';

    public function get($pattern, $callback) {
        $this->addRoute('GET', $pattern, $callback);
    }

    public function post($pattern, $callback) {
        $this->addRoute('POST', $pattern, $callback);
    }

    public function both($pattern, $callback) {
        $this->addRoute('GET', $pattern, $callback);
        $this->addRoute('POST', $pattern, $callback);
    }

    public function mount($prefix, $callback) {
        $previousPrefix = $this->prefix;
        $this->prefix .= $prefix;
        $callback();
        $this->prefix = $previousPrefix;
    }

    public function addRoute($method, $pattern, $callback) {
        $pattern = rtrim($this->prefix . $pattern, '/');
        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'callback' => $callback,
        ];
    }

    public function before($pattern, $callback) {
        $pattern = rtrim($this->prefix . $pattern, '/');
        $this->beforeCallback[] = [
            'pattern' => $pattern,
            'callback' => $callback,
        ];
    }

    public function run() {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $requestUri = rtrim($requestUri, '/');

        // public function before($callback) {
        //     $this->beforeCallback = $callback;
        // }
        // if ($this->beforeCallback) {
        //     call_user_func($this->beforeCallback);
        // }

        foreach ($this->beforeCallback as $before) {
            $pattern = preg_replace('/\{(.*?)\}/', '(.*?)', $before['pattern']);
            $pattern = preg_replace('/\(([^)]+)\)\?/', '(?:\1)?', $pattern); // Handle optional segments

            if (preg_match('#^' . $pattern . '$#', $requestUri, $matches)) {
                array_shift($matches);
                $result = call_user_func_array($before['callback'], $matches);
                if ($result !== null) {
                    return $result;
                }
            }
        }

        foreach ($this->routes as $route) {
            if ($requestMethod == $route['method']) {
                $pattern1 = preg_replace('/\{(\w+)\}/', '(\w+)', $route['pattern']);
                $pattern2 = preg_replace('/\{(\w+)\}/', '([a-zA-Z0-9-_]+)', $route['pattern']);
                
                // $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $route);
                // $pattern = '#^' . $pattern . '$#';
                // if (preg_match($pattern, $uri, $matches)) {
                //     array_shift($matches);
                //     return call_user_func_array($callback, $matches);
                // }


                $pattern = preg_replace('/\{(.*?)\}/', '(.*?)', $route['pattern']);
                $pattern = preg_replace('/\(([^)]+)\)\?/', '(?:\1)?', $pattern); // Handle optional segments

                if (preg_match('#^' . $pattern . '$#', $requestUri, $matches)) {
                    array_shift($matches);
                    call_user_func_array($route['callback'], $matches);
                    return;
                }
            }
        }

        echo '404 Not Found';
    }
}





// $router = new Router();

// $router->before('GET', '/users', function() {
//     echo 'Before users';
// });

// $router->get('/', function() {
//     echo 'Home page';
// });

// $router->get('/users', function() {
//     echo 'List of users';
// });

// $router->get('/users/{id}', function($id) {
//     echo 'User ID: ' . $id;
// });

// $router->mount('/admin', function () use ($router) {
//     $router->get('/', function() {
//         echo 'Admin home page';
//     });

//     $router->get('/users', function() {
//         echo 'Admin list of users';
//     });

//     $router->get('/users/{id}', function($id) {
//         echo 'Admin user ID: ' . $id;
//     });
// });

// $router->run();