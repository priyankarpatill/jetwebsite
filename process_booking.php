<?php
include 'config.php';

// Function to sanitize input data
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize the form data
    $trip_type = sanitizeInput($_POST['trip_type']);
    $mobile = sanitizeInput($_POST['mobile']);
    $email = sanitizeInput($_POST['email']);
    $passengers = sanitizeInput($_POST['passengers']);

// Normalize all inputs to arrays for consistency
$departure = is_array($_POST['departure']) ? $_POST['departure'] : [$_POST['departure']];
$arrival = is_array($_POST['arrival']) ? $_POST['arrival'] : [$_POST['arrival']];
$departure_date = is_array($_POST['departure_date']) ? $_POST['departure_date'] : [$_POST['departure_date']];
$return_date = isset($_POST['return_date']) 
    ? (is_array($_POST['return_date']) ? $_POST['return_date'] : [$_POST['return_date']]) 
    : array_fill(0, count($departure), null); // if no return date


    // 1. Insert booking info
    $stmt = $conn->prepare("INSERT INTO bookings (trip_type, mobile, email, passengers) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $trip_type, $mobile, $email, $passengers);
    
    if ($stmt->execute()) {
        $booking_id = $stmt->insert_id;
    } else {
        echo "Error: " . $stmt->error;
        exit;
    }
    $stmt->close();

    // 2. Insert each segment
    $stmt_seg = $conn->prepare("INSERT INTO booking_segments (booking_id, departure_location, arrival_location, departure_date, return_date) VALUES (?, ?, ?, ?, ?)");

    foreach ($departure as $index => $dep) {
        $arr = $arrival[$index];
        $dep_date = $departure_date[$index];
        $ret_date = isset($return_date[$index]) ? $return_date[$index] : null;

        $stmt_seg->bind_param("issss", $booking_id, $dep, $arr, $dep_date, $ret_date);

        if (!$stmt_seg->execute()) {
            echo "Error inserting segment: " . $stmt_seg->error;
        }
    }

    $stmt_seg->close();
    $conn->close();

    echo "Booking successful!";
}
?>





<!-- 
$stmt_seg->close();
$conn->close();

// ✅ Email sending starts here

// Company email
$company_email = "yourcompany@example.com";  // 🔁 Replace with your actual company email

// Subject
$subject = "New Flight Booking Confirmation";

// Message body
$message = "Booking Details:\n\n";
$message .= "Trip Type: $trip_type\n";
$message .= "Mobile: $mobile\n";
$message .= "Email: $email\n";
$message .= "Passengers: $passengers\n\n";

foreach ($departure as $index => $dep) {
    $arr = $arrival[$index];
    $dep_date = $departure_date[$index];
    $ret_date = isset($return_date[$index]) ? $return_date[$index] : 'N/A';

    $message .= "Segment " . ($index + 1) . ":\n";
    $message .= "From: $dep\nTo: $arr\nDeparture Date: $dep_date\nReturn Date: $ret_date\n\n";
}

// Headers
$headers = "From: no-reply@yourdomain.com"; // 🔁 Replace with your domain or email sender

// Send email to customer
mail($email, $subject, $message, $headers);

// Send email to company
mail($company_email, $subject, $message, $headers);

// ✅ Final output
echo "Booking successful!"; -->
