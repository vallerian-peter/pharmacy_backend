<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Shapes the login / me API response.
 *
 * Expected output:
 * {
 *   "token": "sanctum-token",
 *   "user": {
 *     "uuid": "...",
 *     "name": "Asha Mwangi",
 *     "username": "asha",
 *     "email": "asha@pharmacy.com",
 *     "assignedBranch": {
 *       "uuid": "...",
 *       "name": "Kariakoo",
 *       "address": "...",
 *       "phone": "...",
 *       "invoicePrefix": "KRK",
 *       "isMainBranch": true
 *     },
 *     "role": "Branch Manager",
 *     "status": "Active",
 *     "permissions": [
 *       "POS_CREATE",
 *       "POS_VIEW",
 *       "..."
 *     ],
 *     "createdAt": "2026-09-08T18:00:00+00:00",
 *     "updatedAt": "2026-09-08T18:00:00+00:00"
 *   }
 * }
 */

class LoginResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $branch = $this->resource['branch'] ?? null;
        $branchData = $branch ? [
            'uuid'          => $branch->uuid,
            'name'          => $branch->name,
            'address'       => $branch->address,
            'phone'         => $branch->phone,
            'invoicePrefix' => $branch->invoicePrefix,
            'isMainBranch'  => (bool) $branch->isMainBranch,
        ] : null;

        return [
            'token'              => $this->resource['token'] ?? null,
            'user'               => [
                'uuid'           => $this->resource['user']->uuid,
                'name'           => $this->resource['user']->name,
                'username'       => $this->resource['user']->username,
                'email'          => $this->resource['user']->email,
                'assignedBranch' => $branchData,
                'role'           => $this->resource['role'] ?? null,
                'status'         => $this->resource['status'] ?? $this->resource['user']->status?->value ?? $this->resource['user']->status,
                'permissions'    => $this->resource['permissions'] ?? [],
                'createdAt'      => $this->resource['user']->createdAt?->toIso8601String(),
                'updatedAt'      => $this->resource['user']->updatedAt?->toIso8601String(),
            ],
        ];
    }
}
