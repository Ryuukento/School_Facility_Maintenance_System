<?php

namespace Tests\Support;

/**
 * Session-based authentication helpers for Feature tests that exercise
 * routes guarded by EnsureApiAuthenticated / EnsureRole, which read the
 * 'auth_user' session key (set by the legacy PHP login flow / SyncLegacyPhpSession).
 */
trait InteractsWithLegacySession
{
    /**
     * Authenticate the test client as the given user via the session-based
     * 'auth_user' key that EnsureApiAuthenticated / EnsureRole read.
     */
    protected function actingAsSessionUser(int $userId, string $role): self
    {
        return $this->withSession([
            'auth_user' => ['user_id' => $userId, 'role' => $role],
        ]);
    }

    /**
     * Same as actingAsSessionUser(), but also sets the flat 'user_id'/'role'
     * session keys that some controllers (e.g. ItemController) read directly
     * for activity logging, in addition to the nested 'auth_user' array.
     */
    protected function actingAsSessionUserWithFlatKeys(int $userId, string $role): self
    {
        return $this->withSession([
            'auth_user' => ['user_id' => $userId, 'role' => $role],
            'user_id' => $userId,
            'role' => $role,
        ]);
    }
}
