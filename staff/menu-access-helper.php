<?php

if (!function_exists('hasMenuAccess')) {
    function hasMenuAccess($conn, $userId, $menuKey, $defaultAllowed) {
        // Query to check role first
        $roleQuery = mysqli_query($conn, "SELECT jabatan FROM users WHERE id = '$userId'");
        if ($roleQuery && mysqli_num_rows($roleQuery) > 0) {
            $user = mysqli_fetch_assoc($roleQuery);
            // Super Admin always has access to everything
            if ($user['jabatan'] === 'Super Admin') {
                return true;
            }
        }

        // Ensure table exists (safeguard)
        $checkTable = mysqli_query($conn, "SHOW TABLES LIKE 'user_menu_access'");
        if ($checkTable && mysqli_num_rows($checkTable) > 0) {
            $menuKeyEscaped = mysqli_real_escape_string($conn, $menuKey);
            $query = mysqli_query($conn, "SELECT is_allowed FROM user_menu_access WHERE user_id = '$userId' AND menu_key = '$menuKeyEscaped'");
            if ($query && mysqli_num_rows($query) > 0) {
                $row = mysqli_fetch_assoc($query);
                return intval($row['is_allowed']) === 1;
            }
        }

        return $defaultAllowed;
    }
}
?>
