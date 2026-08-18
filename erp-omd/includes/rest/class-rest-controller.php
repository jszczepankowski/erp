<?php

abstract class ERP_OMD_REST_Controller
{
    const NAMESPACE = 'erp-omd/v1';

    protected function register_route($route, array $definitions)
    {
        register_rest_route(self::NAMESPACE, $route, $definitions);
    }

    protected function endpoint($methods, $callback, $permission_callback)
    {
        return [
            'methods' => $methods,
            'callback' => $callback,
            'permission_callback' => $permission_callback,
        ];
    }

    protected function readable($callback, $permission_callback)
    {
        return $this->endpoint(WP_REST_Server::READABLE, $callback, $permission_callback);
    }

    protected function creatable($callback, $permission_callback)
    {
        return $this->endpoint(WP_REST_Server::CREATABLE, $callback, $permission_callback);
    }

    protected function editable($callback, $permission_callback)
    {
        return $this->endpoint(WP_REST_Server::EDITABLE, $callback, $permission_callback);
    }

    protected function deletable($callback, $permission_callback)
    {
        return $this->endpoint(WP_REST_Server::DELETABLE, $callback, $permission_callback);
    }

    protected function int_param($request, $key)
    {
        return (int) $request[$key];
    }

    protected function text_param(WP_REST_Request $request, $key, $default = '')
    {
        $value = $request->get_param($key);
        if ($value === null) {
            return (string) $default;
        }

        return sanitize_text_field((string) $value);
    }
}
