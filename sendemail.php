<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'booking-errors.log');

// Load Composer autoloader and config
require 'vendor/autoload.php';
require 'emailconfig.php';
include 'config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Sanitize function
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Function to send booking email and backup
function sendBookingEmail($trip_type, $mobile, $email, $passengers, $segments) {
    $timestamp = date('Y-m-d_H-i-s');
    $random = substr(md5(rand()), 0, 7);
    $filename = "booking_submissions/{$timestamp}_{$random}.txt";

    if (!file_exists('booking_submissions')) {
        mkdir('booking_submissions', 0777, true);
    }

    $content = "New Flight Booking Submission\n";
    $content .= "===========================\n";
    $content .= "Date: " . date('Y-m-d H:i:s') . "\n";
    $content .= "Trip Type: $trip_type\n";
    $content .= "Mobile: $mobile\n";
    $content .= "Email: $email\n";
    $content .= "Passengers: $passengers\n\n";

    foreach ($segments as $index => $seg) {
        $content .= "Segment " . ($index + 1) . ":\n";
        $content .= "From: {$seg['departure']}\n";
        $content .= "To: {$seg['arrival']}\n";
        $content .= "Departure Date: {$seg['departure_date']}\n";
        $content .= "Return Date: {$seg['return_date']}\n\n";
    }

    $fileSaved = file_put_contents($filename, $content);
    if ($fileSaved) error_log("Booking saved to file: $filename");

    // Send email with PHPMailer
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = SMTP_AUTH;

        if (SMTP_AUTH) {
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
        }

        if (SMTP_SECURE === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif (SMTP_SECURE === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPAutoTLS = false;
            $mail->SMTPSecure = false;
        }

        $mail->Port = SMTP_PORT;
        $mail->setFrom(EMAIL_FROM, EMAIL_FROM_NAME);
        $mail->addAddress($email); // Customer
        $mail->addAddress(EMAIL_TO, EMAIL_TO_NAME); // Admin/Company
        $mail->addReplyTo($email);

        $mail->isHTML(true);
        $mail->Subject = "New Flight Booking Received";

        $htmlBody = "
            <h2>Booking Details</h2>
            <p><strong>Trip Type:</strong> $trip_type</p>
            <p><strong>Mobile:</strong> $mobile</p>
            <p><strong>Email:</strong> $email</p>
            <p><strong>Passengers:</strong> $passengers</p>
            <hr>
        ";

        foreach ($segments as $index => $seg) {
            $htmlBody .= "<h4>Segment " . ($index + 1) . "</h4>";
            $htmlBody .= "<p><strong>From:</strong> {$seg['departure']}<br>";
            $htmlBody .= "<strong>To:</strong> {$seg['arrival']}<br>";
            $htmlBody .= "<strong>Departure Date:</strong> {$seg['departure_date']}<br>";
            $htmlBody .= "<strong>Return Date:</strong> {$seg['return_date']}</p>";
        }

        $mail->Body = $htmlBody;
        $mail->AltBody = strip_tags(str_replace('<br>', "\n", $htmlBody));
        $mail->send();
        error_log("Email sent successfully for booking.");
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer Error: {$e->getMessage()}");
        return $fileSaved ? true : false;
    }
}

// Handle form POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $trip_type = sanitizeInput($_POST['trip_type']);
    $mobile = sanitizeInput($_POST['mobile']);
    $email = sanitizeInput($_POST['email']);
    $passengers = sanitizeInput($_POST['passengers']);

    $departure = is_array($_POST['departure']) ? $_POST['departure'] : [$_POST['departure']];
    $arrival = is_array($_POST['arrival']) ? $_POST['arrival'] : [$_POST['arrival']];
    $departure_date = is_array($_POST['departure_date']) ? $_POST['departure_date'] : [$_POST['departure_date']];
    $return_date = isset($_POST['return_date']) 
        ? (is_array($_POST['return_date']) ? $_POST['return_date'] : [$_POST['return_date']]) 
        : array_fill(0, count($departure), null);

    // 1. Insert booking info
    $stmt = $conn->prepare("INSERT INTO bookings (trip_type, mobile, email, passengers) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $trip_type, $mobile, $email, $passengers);
    
    if (!$stmt->execute()) {
        echo "Error: " . $stmt->error;
        exit;
    }
    $booking_id = $stmt->insert_id;
    $stmt->close();

    // 2. Insert segments
    $stmt_seg = $conn->prepare("INSERT INTO booking_segments (booking_id, departure_location, arrival_location, departure_date, return_date) VALUES (?, ?, ?, ?, ?)");
    $segments = [];

    foreach ($departure as $i => $dep) {
        $arr = $arrival[$i];
        $dep_date = $departure_date[$i];
        $ret_date = $return_date[$i] ?? null;

        $stmt_seg->bind_param("issss", $booking_id, $dep, $arr, $dep_date, $ret_date);
        $stmt_seg->execute();

        $segments[] = [
            'departure' => $dep,
            'arrival' => $arr,
            'departure_date' => $dep_date,
            'return_date' => $ret_date ?: 'N/A'
        ];
    }

    $stmt_seg->close();
    $conn->close();

    // Send email & backup
    $emailSent = sendBookingEmail($trip_type, $mobile, $email, $passengers, $segments);

    echo $emailSent ? "Booking successful and email sent!" : "Booking saved but email failed.";
}
?>
