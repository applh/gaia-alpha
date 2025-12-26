<?php

namespace GaiaAlpha;

class Pipeline
{
    private array $middlewares = [];

    /**
     * Set the middlewares to be executed.
     *
     * @param array $middlewares
     * @return $this
     */
    public function send(array $middlewares)
    {
        $this->middlewares = $middlewares;
        return $this;
    }

    /**
     * Run the pipeline with a final destination callback.
     *
     * @param \Closure $destination
     * @return mixed
     */
    public function then(\Closure $destination)
    {
        $pipeline = array_reduce(
            array_reverse($this->middlewares),
            function ($stack, $middleware) {
                return function () use ($stack, $middleware) {
                    if (is_string($middleware) && class_exists($middleware)) {
                        $middleware = new $middleware();
                    }

                    if ($middleware instanceof Middleware) {
                        return $middleware->handle($stack);
                    }

                    return $stack();
                };
            },
            $destination
        );

        return $pipeline();
    }
}
