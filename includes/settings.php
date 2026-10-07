<?php
// includes/settings.php

function get_setting($conn, $key, $default = null) {
    $stmt = mysqli_prepare($conn, "SELECT setting_value FROM settings WHERE setting_key = ?");
    mysqli_stmt_bind_param($stmt, "s", $key);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $row ? $row["setting_value"] : $default;
}

function update_setting($conn, $key, $value) {
    $stmt = mysqli_prepare($conn, "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
                                    ON DUPLICATE KEY UPDATE setting_value = ?");
    mysqli_stmt_bind_param($stmt, "sss", $key, $value, $value);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

function get_all_settings($conn) {
    $result = mysqli_query($conn, "SELECT setting_key, setting_value FROM settings");
    $settings = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $settings[$row["setting_key"]] = $row["setting_value"];
    }
    return $settings;
}
?>
