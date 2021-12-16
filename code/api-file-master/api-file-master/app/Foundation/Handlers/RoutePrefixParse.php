<?php

namespace App\Foundation\Handlers;

use ReflectionException;

/**
 * Class RoutePrefixParse
 * @package App\Foundation
 */
class RoutePrefixParse
{
    /** @var int 索引 */
    private $index = 1;
    /** @var string 路由前缀 */
    private $routePrefix;

    /**
     * RoutePrefixParse constructor.
     *
     * @param  string  $routePrefix
     */
    public function __construct($routePrefix = 'developer')
    {
        $this->routePrefix = $routePrefix;
    }

    /**
     * 打印路由列表
     * @return string
     * @throws ReflectionException
     */
    public function getRouteListHtml() : string
    {
        $routes = app()->router->getRoutes();
        $html   = "<div style='margin: 60px 60px'>";
        $html   .= '<div style="font-size: 22px;">开发工具列表</div><br/>';
        foreach ($routes as $key => $route) {
            if (str_contains($route['uri'], $this->routePrefix)) {
                [$class, $method] = explode('@', $route['action']['uses']);
                $reflection = new \ReflectionClass ($class);
                if (!$reflection->hasMethod($method)) {
                    continue;
                }
                $refMethod   = $reflection->getMethod($method);
                $docDesc     = (new DocParser())->parse($refMethod->getDocComment());
                $description = $docDesc['description'] ?? "";
                $devUrl      = get_api_uri(ltrim($route['uri'], '/') ?? "");
                $method      = $route['method'] ?? "GET";
                $html        .= "<span style='color: dodgerblue;'>$this->index.$method.$description</span>" . "--" . $devUrl . str_repeat("<br/>", 2);
                $this->index++;
            }
        }
        $html .= "</div>";

        return $html;
    }
}
