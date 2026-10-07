<?php
// includes/notifications.php
require_once __DIR__ . "/../config/config.php";

// ===================================================================
// CORE: create a notification + fan it out to every matching channel
// $channels can include 'dashboard' (always saved), 'email', 'sms'
// $is_automatic marks whether this was system-triggered (e.g. new book)
// vs manually composed by a staff member.
// ===================================================================
function send_notification($conn, $sender_id, $type, $title, $message, $recipient_type, $recipient_value = null, $channels = ["dashboard"], $is_automatic = false) {

    $recipients = get_matching_recipients($conn, $recipient_type, $recipient_value);
    $recipient_count = count($recipients);

    $email_sent_count = 0;
    $sms_sent_count = 0;

    if (in_array("email", $channels)) {
        foreach ($recipients as $r) {
            if (!empty($r["email"]) && send_email($r["email"], $title, $message)) {
                $email_sent_count++;
            }
        }
    }

    if (in_array("sms", $channels)) {
        foreach ($recipients as $r) {
            if (!empty($r["phone_number"]) && send_sms($r["phone_number"], $title . ": " . $message)) {
                $sms_sent_count++;
            }
        }
    }

    $query = "INSERT INTO notifications
              (sender_id, type, title, message, recipient_type, recipient_value, is_automatic, recipient_count, email_sent_count, sms_sent_count)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    $is_automatic_int = $is_automatic ? 1 : 0;
    mysqli_stmt_bind_param(
        $stmt, "isssssiiii",
        $sender_id, $type, $title, $message, $recipient_type, $recipient_value,
        $is_automatic_int, $recipient_count, $email_sent_count, $sms_sent_count
    );
    $success = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $success;
}

// Resolves "all" / "department" / "student" into a real list of
// {email, phone_number} to actually deliver to.
function get_matching_recipients($conn, $recipient_type, $recipient_value) {
    if ($recipient_type === "all") {
        $query = "SELECT email, phone_number FROM users WHERE role = 'student'";
        $result = mysqli_query($conn, $query);
    } elseif ($recipient_type === "department") {
        $query = "SELECT email, phone_number FROM users WHERE role = 'student' AND department = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $recipient_value);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    } else { // student
        $query = "SELECT email, phone_number FROM users WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        $id = intval($recipient_value);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    }

    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}

// ===================================================================
// EMAIL CHANNEL
// Real delivery requires PHPMailer + SMTP credentials in config.php.
// Until EMAIL_ENABLED is true, this safely does nothing and returns
// false rather than pretending to send.
// ===================================================================
function send_email($to_email, $subject, $body) {
    if (!EMAIL_ENABLED) {
        return false;
    }

    // Requires PHPMailer's src files placed in lib/PHPMailer/ (see
    // lib/PHPMailer/README.txt for exactly where to get them from)
    require_once __DIR__ . "/../lib/PHPMailer/Exception.php";
    require_once __DIR__ . "/../lib/PHPMailer/PHPMailer.php";
    require_once __DIR__ . "/../lib/PHPMailer/SMTP.php";

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = 'tls';
        $mail->Port = SMTP_PORT;
        $mail->setFrom(SMTP_USERNAME, SMTP_FROM_NAME);
        $mail->addAddress($to_email);
        $mail->Subject = $subject;
        $mail->Body = $body;
        return $mail->send();
    } catch (Exception $e) {
        return false;
    }
}

// ===================================================================
// SMS CHANNEL
// Real delivery requires a paid SMS gateway account (Termii, Africa's
// Talking, Twilio, etc). Until SMS_ENABLED is true, this safely does
// nothing and returns false rather than pretending to send.
// ===================================================================
function send_sms($phone_number, $message) {
    if (!SMS_ENABLED) {
        return false;
    }

    // Example shape for a typical REST-based SMS gateway (adjust to
    // whichever provider you sign up with - check their API docs):
    //
    // $response = file_get_contents("https://api.yourprovider.com/send?" . http_build_query([
    //     "api_key" => SMS_API_KEY,
    //     "to" => $phone_number,
    //     "from" => SMS_SENDER_ID,
    //     "sms" => $message
    // ]));
    // return $response !== false;

    return false;
}

// ===================================================================
// READ HELPERS
// ===================================================================

function get_all_notifications($conn, $limit = 100) {
    $query = "SELECT n.*, u.full_name AS sender_name
              FROM notifications n
              JOIN users u ON n.sender_id = u.id
              ORDER BY n.created_at DESC
              LIMIT ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    mysqli_stmt_close($stmt);
    return $rows;
}

function get_notifications_for_student($conn, $student_id, $department, $limit = 10) {
    $query = "SELECT n.*, u.full_name AS sender_name
              FROM notifications n
              JOIN users u ON n.sender_id = u.id
              WHERE n.recipient_type = 'all'
                 OR (n.recipient_type = 'department' AND n.recipient_value = ?)
                 OR (n.recipient_type = 'student' AND n.recipient_value = ?)
              ORDER BY n.created_at DESC
              LIMIT ?";
    $student_id_str = strval($student_id);
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ssi", $department, $student_id_str, $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    mysqli_stmt_close($stmt);
    return $rows;
}
?>
