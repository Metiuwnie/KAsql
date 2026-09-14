<?php
class App {
    protected $controller = 'Dashboard';
    protected $method = 'index';
    protected $params = [];

    public function __construct() {
        $url = $this->parseUrl();

        // 0. API Route Detection — delegate to REST API router
        if (isset($url[0]) && strtolower($url[0]) === 'api') {
            $apiUrl = implode('/', $url);
            require_once __DIR__ . '/../app/controllers/Api/ApiRouter.php';
            new ApiRouter($apiUrl);
            return;
        }

        // 1. Controller
        if (isset($url[0])) {
            $controllerName = ucfirst($url[0]); // e.g., 'jurnal' -> 'Jurnal'
            if (file_exists(__DIR__ . '/../app/controllers/' . $controllerName . '.php')) {
                $this->controller = $controllerName;
                unset($url[0]);
            }
        }

        require_once __DIR__ . '/../app/controllers/' . $this->controller . '.php';
        $this->controller = new $this->controller;

        // 2. Method
        if (isset($url[1])) {
            if (method_exists($this->controller, $url[1])) {
                $this->method = $url[1];
                unset($url[1]);
            }
        }

        // 3. Parameters
        if (!empty($url)) {
            $this->params = array_values($url);
        }

        // Jalankan Controller & Method, serta kirim parameter jika ada
        call_user_func_array([$this->controller, $this->method], $this->params);
    }

    public function parseUrl() {
        if (isset($_GET['url'])) {
            $url = rtrim($_GET['url'], '/');
            $url = filter_var($url, FILTER_SANITIZE_URL);
            $url = explode('/', $url);
            return $url;
        }
        return [];
    }
}
