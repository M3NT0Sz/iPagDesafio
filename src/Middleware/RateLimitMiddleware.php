<?php
namespace App\Middleware;


use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface;

class RateLimitMiddleware
{
    private $limit;
    private $window;
    private static $requests = [];

    public function __construct($limit = 10, $window = 60)
    {
        $this->limit = $limit;
        $this->window = $window;
    }

    public function __invoke(
        \Psr\Http\Message\ServerRequestInterface $request,
        \Psr\Http\Server\RequestHandlerInterface $handler
    ): \Psr\Http\Message\ResponseInterface {
        $ip = $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';
        $now = time();
        if (!isset(self::$requests[$ip])) {
            self::$requests[$ip] = [];
        }
        // Remove requests outside the window
        self::$requests[$ip] = array_filter(self::$requests[$ip], function($timestamp) use ($now) {
            return ($now - $timestamp) < $this->window;
        });
        if (count(self::$requests[$ip]) >= $this->limit) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode(['error' => 'Rate limit exceeded']));
            return $response->withStatus(429)->withHeader('Content-Type', 'application/json');
        }
        self::$requests[$ip][] = $now;
        return $handler->handle($request);
    }
}
