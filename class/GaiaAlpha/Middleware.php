<?php

namespace GaiaAlpha;

interface Middleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure $next The next middleware or handler in the pipeline.
     * @return mixed
     */
    public function handle(\Closure $next);
}
