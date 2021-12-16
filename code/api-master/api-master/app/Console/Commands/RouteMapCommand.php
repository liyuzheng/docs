<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

class RouteMapCommand extends Command
{
    protected $signature   = 'route_map';
    protected $description = '路由集合';

    public function __construct()
    {
        parent::__construct();
    }

    public function getRouteList()
    {
        $path   = [];
        $routes = Route::getRoutes();
        foreach ($routes as $k => $value) {
            $path[] = $value['uri'];
        }

        return $path;
    }

    public function handle()
    {
        /** @var  string 假设的请求过来的路由 $route */
        $route = "v1/users/12121/tags";
        $url   = $this->mapRoute($route);
        dd($url);
    }

    protected function mapRoute($route)
    {
        $routesMap = config('routes_map');
        $route     = ltrim($route, '/');
        preg_match_all('/\/\d{1,}/', $route, $match);
        $replaces = $match[0] ?? [];
        foreach ($replaces as $replace) {
            $route = str_replace($replace, "/*", $route);
        }

        return $routesMap[$route] ?? "";
    }

    /**
     * 生成路由map
     */
    protected function putMapToTile()
    {
        $data   = [];
        $result = $this->getRouteList();
        foreach ($result as $res) {
            $start        = ltrim($this->replaceBracketToStar($res),'/');
            $data[$start] = 'all' . $res;
        }

        $data = var_export($data, true);
        file_put_contents(public_path("route_map.php"), $data);
    }

    /**
     * 正则替换为*号
     *
     * @param $route
     *
     * @return string|string[]
     */
    protected function replaceNumberToStar($route)
    {
        preg_match_all('/\d{1,}/', $route, $match);
        $replaces = $match[0] ?? [];
        foreach ($replaces as $replace) {
            $route = str_replace($replace, "*", $route);
        }

        return $route;
    }

    /**
     * 正则替换花括号
     *
     * @param $route
     *
     * @return string|string[]
     */
    protected function replaceBracketToStar($route)
    {
        preg_match_all('/{\w{1,}}/', $route, $match);
        $replaces = $match[0] ?? [];
        foreach ($replaces as $replace) {
            $route = str_replace($replace, "*", $route);
        }

        return $route;
    }
}
