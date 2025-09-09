<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;
use Config\Services;

class RolePermissionFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $auth = Services::auth(); // however you load logged-in user
        $user = $auth->user();    // your logged-in user object

        if (!$user) {
            return Services::response()
                ->setJSON(['message' => 'Unauthorized'])
                ->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED);
        }

        // 🔹 Convert ["role=ADMIN", "permission=ManageSAPUpload"] → ['role' => 'ADMIN', 'permission' => 'ManageSAPUpload']
        $args = [];
        if (is_array($arguments)) {
            foreach ($arguments as $arg) {
                if (strpos($arg, '=') !== false) {
                    [$key, $value] = explode('=', $arg, 2);
                    $args[strtolower($key)] = $value;
                }
            }
        }


        $requiredRole = $args['role'] ?? null;
        $requiredPermission = $args['permission'] ?? null;
        // print_r($args);
        // print_r('requiredRole '.$requiredRole.'<br>');
        // print_r('user role '.$user->role.'<br>');
        // print_r($user->permissions);
        // print_r($requiredRole && $user->role === strtoupper($requiredRole) ? 'true' : 'false');
        // print_r($requiredPermission && in_array($requiredPermission, $user->permissions ?? []) ? 'true' : 'false');
        // print_r($requiredRole);
        // print_r($requiredPermission);
        // die;
        // 1 If role matches → allow
        if ($requiredRole && $user->role === strtoupper($requiredRole)) {
            return;
        }

        // 2 If permission matches → allow
        if ($requiredPermission && in_array($requiredPermission, $user->permissions ?? [])) {
            return;
        }

        // 3 Else reject
        return Services::response()
            ->setJSON(['message' => 'Forbidden'])
            ->setStatusCode(ResponseInterface::HTTP_FORBIDDEN);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Nothing required after
    }
}
