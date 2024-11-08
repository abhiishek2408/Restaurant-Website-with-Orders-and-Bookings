<?php
session_start(); // Start the session
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// include('Occasion.php');
include('config/db.php'); // Include the database configuration file

// Initialize variables
$partySize = $reservationDate = $userId = $AvlSlots = $eventType = $specialRequests = "";
$errors = [];
$message = ""; // Variable to hold the success message

// Function to handle errors
function displayErrors($errors)
{
    return implode('<br>', array_map(fn($error) => "<p class='error'>$error</p>", $errors));
}

// Check if the form is submitted via AJAX
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ajax'])) {
    // Collect and sanitize input data
    $partySize = htmlspecialchars(trim($_POST['party_size']));
    $reservationDate = htmlspecialchars(trim($_POST['reservation_date']));
    $AvlSlots = htmlspecialchars(trim($_POST['slots']));
    $eventType = htmlspecialchars(trim($_POST['event_type']));
    $specialRequests = htmlspecialchars(trim($_POST['special_requests']));

    // Validate input data
    if (empty($partySize) || !is_numeric($partySize)) {
        $errors[] = "Valid party size is required.";
    }

    if (empty($reservationDate) || !DateTime::createFromFormat('Y-m-d', $reservationDate)) {
        $errors[] = "Valid reservation date is required.";
    }

    if (empty($eventType)) {
        $errors[] = "Event type is required.";
    }

    if (empty($AvlSlots)) {
        $errors[] = "Slots option is required.";
    }

    // If no errors, check for existing reservations
    if (empty($errors)) {
        if (isset($_SESSION['user']['user_id'])) {
            $userId = (int)$_SESSION['user']['user_id']; // Ensure it's an integer
        } else {
            $errors[] = "User is not logged in.";
        }

        // Prepare the SQL statement to check for existing reservations
        $sql = "SELECT * FROM reservations WHERE user_id = ? AND reservation_date = ? AND slots = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt === false) {
            die("Error preparing statement: " . $conn->error);
        }

        // Bind parameters
        $stmt->bind_param("iss", $userId, $reservationDate, $AvlSlots);

        // Execute the statement
        if (!$stmt->execute()) {
            die("Error executing statement: " . $stmt->error);
        }

        // Get the result
        $result = $stmt->get_result();

        // Check if a reservation already exists
        if ($result->num_rows > 0) {
            // If reservation exists, return an error
            $errors[] = "You have already booked a reservation for this date and slot.";
        } else {
            // No existing reservation, proceed with booking

            // Prepare the SQL statement for inserting a new reservation
            $sqlInsert = "INSERT INTO reservations (user_id, party_size, reservation_date, slots, event_type, special_requests) 
                          VALUES (?, ?, ?, ?, ?, ?)";
            $insertStmt = $conn->prepare($sqlInsert);

            if ($insertStmt === false) {
                die("Error preparing insert statement: " . $conn->error);
            }

            // Bind parameters for the insert statement
            $insertStmt->bind_param(
                "isssss", // 1 integer for user_id, 5 strings for the rest
                $userId,
                $partySize,
                $reservationDate,
                $AvlSlots,
                $eventType,
                $specialRequests
            );

            // Execute the insert statement
            if ($insertStmt->execute()) {
                $message = "Your reservation has been successfully made!";
                echo json_encode(['success' => true, 'message' => $message]);
            } else {
                echo json_encode(['success' => false, 'errors' => ["Execution Error: " . $insertStmt->error]]);
            }

            $insertStmt->close(); // Close the insert statement
        }

        // Close the select statement
        $stmt->close();
    }

    // If there are errors, return them as JSON
    if (!empty($errors)) {
        echo json_encode(['success' => false, 'errors' => $errors]);
    }

    exit; // Terminate the script
}
?>


<?php

include('config/db.php'); // Include the database configuration file

// Initialize variables for form data
$first_name = $last_name = $email = $phone = $event_type = $event_date = $message = '';
$error_message = '';
$success_message = '';

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve data from form and session
    $user_id = $_SESSION['user']['user_id'] ?? null; // Ensure user_id exists in session
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $event_type = $_POST['event_type'];
    $event_date = $_POST['event_date'];
    $message = $_POST['message'];
    $state = 'active'; // Default state

    // Debugging output - optional, can be removed in production
    echo "User ID: $user_id<br>";
    echo "First Name: $first_name<br>";
    echo "Last Name: $last_name<br>";
    echo "Email: $email<br>";
    echo "Phone: $phone<br>";
    echo "Event Type: $event_type<br>";
    echo "Event Date: $event_date<br>";
    echo "Message: $message<br>";
    echo "State: $state<br>";

    // Prepare and bind statement
    $stmt = $conn->prepare("INSERT INTO occasion (user_id, first_name, last_name, email, phone, event_type, event_date, message, state) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

    if ($stmt === false) {
        die('Prepare failed: ' . htmlspecialchars($conn->error));
    }

    // Bind parameters
    $stmt->bind_param("issssssss", $user_id, $first_name, $last_name, $email, $phone, $event_type, $event_date, $message, $state);

    // Execute statement
    if ($stmt->execute()) {
        $success_message = "New occasion recorded successfully.";
    } else {
        $error_message = "Error: " . $stmt->error; // Show error if execution fails
    }

    // Close statement and connection
    $stmt->close();
    $conn->close();
}
?>









<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Management System</title>
    <link rel="stylesheet" type="text/css" href="css/styles.css">
    <link rel="stylesheet" type="text/css" href="OccasionStyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>



    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            color: #333;

            transition: background-color 0.3s, color 0.3s;
        }

        h1,
        h2 {
            color: #0056b3;
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            /* Allow items to wrap */
            background-color: #2C2C2C;
            padding: 10px;

        }

        nav h1 {
            margin-left: 20px;
            font-size: 24px;
            color: #FF7518;
            font-family: 'Playfair Display', serif;
            font-weight: bold;
            letter-spacing: 1px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
            line-height: 1.5;
            transition: color 0.3s ease;
          
        }

        .nav-links {
            display: flex;
            align-items: center;
            margin-left: 20px;
        }

        nav ul {
            list-style-type: none;
            padding: 0;
            margin: 0;
            display: flex;
            margin-right: auto;
            margin-left: 50%;
        }

        nav ul li {
            display: inline;
            /* Displays items in a line */
            margin-right: 15px;
            position: relative;
            /* Needed for dropdown positioning */
        }

        nav ul li a {
            color: white;
            font-family: 'Roboto', sans-serif;
            /* Applying the font */
            text-decoration: none;
            padding: 8px;
            transition: background-color 0.3s;
            cursor: pointer;
        }

        nav ul li a:hover {
            background-color: rgba(255, 255, 255, 0.2);

        }

        .dropdown {
            display: none;
            position: absolute;
            background-color: white;
            min-width: 160px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            z-index: 1;
            border-radius: 5px;
            margin-top: 5px;
            right: 0;

        }

        .dropdown a {
            color: #333;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            transition: background-color 0.3s;
        }

        .dropdown a:hover {
            background-color: #f0f0f0;
        }

        .search-container {
            display: flex;
            align-items: center;
            position: relative;
            margin-left: 40%;

        }


        .search-box {
            width: 100%;
            max-width: 200px;
            border: 1px solid #ddd;
            border-radius: 25px;
            padding: 10px 20px;
            outline: none;
            transition: border-color 0.3s;
            font-size: 16px;
        }

        .search-box:focus {
            border-color: #0056b3;
        }

        .search-icon {
            margin-left: -30px;
            cursor: pointer;
            color: #0056b3;
            font-size: 20px;
            pointer-events: auto;
        }

        .dark-mode-toggle,
        .notification-icon,
        .cart-icon {
            cursor: pointer;
            color: #fff;
            margin-left: 15px;
            font-size: 20px;

        }

        .dark-mode-toggle {
            margin-right: 35px;
        }


        .cart-icon i {
            color: white;
        }



        .profile-container {
            position: relative;
            /* Position relative for the dropdown */
            display: inline-block;
            /* Inline-block for proper alignment */
        }

        .profile-picture {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            border: 2px solid #FF7518;
        }

        .dropdown {
            position: absolute;
            /* Absolute position for dropdown */
            background: white;
            /* Dropdown background */
            border: 1px solid #ccc;
            /* Dropdown border */
            z-index: 1000;
            /* Ensure dropdown is above other content */
            width: 200px;
            /* Set a width for the dropdown */
            display: none;
            /* Initially hidden */
        }

        .nav-links {
            display: flex;
            justify-content: space-between;
            /* Align icons evenly */
            padding: 10px;
            /* Padding for nav links */
        }

        .dropdown a {
            display: block;
            /* Block display for links */
            padding: 8px 10px;
            /* Padding for links */
            text-decoration: none;
            /* No underline for links */
            color: black;
            /* Link color */
        }

        .dropdown a:hover {
            background-color: #f0f0f0;
            /* Highlight on hover */
        }



        /* Show dropdown when toggled */
        .show {
            display: block;
        }


        .hamburger {
            display: none;
            flex-direction: column;
            cursor: pointer;

        }

        .hamburger div {
            width: 25px;
            height: 3px;
            background-color: white;
            margin: 3px 0;
            left: 0;
            transition: 0.4s;
        }


        body.dark-mode {
            background-color: #333;
            color: #f0f0f0;
        }

        nav.dark-mode {
            background-color: #444;
        }




        @media (max-width: 1023px) {

            nav h1 {
                font-size: 30px;
                text-align: left;
                margin-right: auto;
            }

            nav ul {
                flex-direction: column;
                align-items: flex-start;
                display: none;
                position: absolute;
                background-color: #0056b3;
                width: 100%;
                left: 0;
                top: 50px;
                z-index: 2;
            }

            nav.active ul {
                display: flex;
            }

            .hamburger {
                background-color: green;
                display: flex;
                justify-content: flex-start;
                margin-right: 2%;
                align-items: left;
            }
        }


        @media (min-width: 1024px) and (max-width: 1300px) {
            nav h1 {
                font-size: 10px;
                color: black;
            }

            nav h1 {
                font-size: 28px;
            }

            nav ul {
                margin-left: 4%;
                margin-right: auto;
            }
        }

        .user-info {
            background-color: #f9f9f9;
            padding: 10px;
            border: 1px solid #ddd;
            margin: 20px 0;
            border-radius: 5px;
        }

        .user-options {
            margin: 20px 0;
            padding: 10px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .user-options ul {
            list-style-type: none;
            padding: 0;
        }

        .user-options li {
            margin: 10px 0;
        }

        .user-options a {
            text-decoration: none;
            color: #4CAF50;
            /* Change the color as needed */
        }

        main {
            padding: 2px;
        }

        .icon {
            background-color: #e3f8e0;
            color: #388e3c;
            padding: 3px 8px;
            border-radius: 5px;
            font-size: 0.8em;
        }

      
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

     

        #hero {
            position: relative;

            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: black;
        }

        /* Overlay to darken the background */
        #hero .overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: white;
            /* Dark overlay for better contrast */
            z-index: 0;
        }

        /* Hero Content Styling */
        .hero-content {
            position: relative;
            z-index: 1;
        }

        .feature-img1 {
            float: right;
            position: absolute;
            margin-left: 70%;
            height: 100%;
            width: 30%;
        }

        .feature-img2 {
            z-index: 1;

            position: absolute;
            top: 18%;
            margin-right: 70%;
            height: 45%;
        }


        .feature-img2 {
            z-index: 1;
            border: 6px solid #FF7518;
            border-radius: 50%;
            position: absolute;
            top: 18%;
            margin-right: 70%;
            height: 45%;
        }

        .feature-img3 {
            z-index: 1;
            position: absolute;
            top: 55%;
            margin-right: 49%;
            height: 10%;
            width: 8%;
            object-fit: cover;
            background-color: none;
            /* Replace with your background color */
            mix-blend-mode: multiply;
        }

        .feature-img4 {
            z-index: 1;
            position: absolute;
            top: 18%;
            margin-right: 92%;
            height: 10%;
            width: 8%;
            object-fit: cover;
            background-color: none;
            /* Replace with your background color */
            mix-blend-mode: multiply;
        }

        .hero-content h1 {
            font-size: 4rem;
            color: #FF7518;
            font-weight: bold;
            margin-bottom: 10px;
            text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.3);
            /* Adds a soft shadow */
        }

        .hero-content p {
            font-size: 1.5rem;
            margin-bottom: 20px;
            letter-spacing: 2px;
            text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.3);
            /* Adds a soft shadow */
        }

        .btn-primary {
            padding: 15px 30px;
            background-color: black;
            color: white;
            font-size: 1.2rem;
            text-decoration: none;
            border-radius: 30px;
            border: 2px solid #FF7518;
            transition: background-color 0.3s ease;
            text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.3);
            /* Adds a soft shadow */
        }

        .btn-primary:hover {
            background-color: #f2f2f2;
            color: #000;
            border: 2px solid #FF7518;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.4);
            /* Deepened shadow on hover */
            transform: translateY(-2px);
            /* Slightly raises button on hover */

        }



        .Bistrofy-container {
            text-align: center;
            background-color: #2C2C2C;
            display: inline-block;
            height: 60%;
            padding: 30px;
            align-items: center;
            justify-content: center;
            border: 1px solid #ffffff;
        }



        .Bistrofy-header {
            font-size: 2.5rem;
            max-width: 600px;

            margin: 0 auto 40px;
            line-height: 1.5;
            color: #FF7518;
            font-family: "Roboto", sans-serif;
            justify-content: center;
            text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.3);
        }


        .Bistrofy-book-button {
            display: inline-block;
            padding: 10px 30px;
            background-color: white;
            color: #2C2C2C;
            border: 2px solid #FF7518;
            border-radius: 25px;
            text-decoration: none;
            transition: background-color 0.3s ease;
            margin-bottom: 20px;
        }

        .Bistrofy-book-button:hover {
            background-color: #FF7518;
            color: white;
            border: 2px solid white;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.4);
            /* Deepened shadow on hover */
            transform: translateY(-2px);
            /* Slightly raises button on hover */
        }

        .Bistrofy-dining {

            display: block;
            margin: 0 auto;
            max-width: 100%;
            height: auto;
        }

        .Bistrofy-dining {
            width: 50%;
            height: 40%;
            display: block;
            margin: 20px auto;
            max-width: 90%;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .Bistrofy-dining:hover {
            transform: scale(1.02);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.4);

        }


        /* Main container */
        .occasion-container {
            font-family: 'Roboto', sans-serif;
            /* Setting Roboto for the entire component */
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            padding: 40px;
            max-width: 1400px;
            margin: 0 auto;
            background-color: #2C2C2C;
            /* Slightly darker teal for better contrast */

            box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.15);
            /* Subtle shadow for depth */
        }

        /* Left Image Section */
        .occasion-image-section {
            flex: 1;
            margin-right: 40px;
        }

        .occasion-image-section img {
            width: 100%;
            max-width: 500px;
            border-radius: 8px;
            object-fit: cover;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
            margin-top: 22px;
            /* Soft shadow */
        }

        .occasion-image-section img:hover {
            transform: scale(1.02);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.4);

        }

        /* Right Text Section */
        .occasion-text-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 0 20px;
        }

        .occasion-icons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            color: #ffffff;
        }

        .occasion-text-section h2 {
            font-size: 2em;
            font-weight: 700;
            /* Bold weight for heading */
            color: #FF7518;
            margin-bottom: 15px;
            line-height: 1.2;
        }

        .occasion-description {
            font-size: 1em;
            font-weight: 300;
            /* Light weight for paragraph */
            color: #d1d1d1;
            margin-bottom: 25px;
            line-height: 1.6;
        }

        /* Button Styling */
        .occasion-button {
            padding: 12px 24px;
            background: none;
            border: 2px solid #ffffff;
            color: #ffffff;
            cursor: pointer;
            font-size: 1em;
            display: inline-block;
            padding: 10px 30px;
            background-color: white;
            color: #2C2C2C;
            border: 2px solid #FF7518;
            border-radius: 25px;
            text-decoration: none;
            transition: background-color 0.3s ease;
            margin-bottom: 20px;
            font-family: 'Roboto', sans-serif;
            font-weight: 500;
            transition: background-color 0.3s, color 0.3s;
            align-self: start;
            margin-top: 10px;
        }

        .occasion-button:hover {
            background-color: #FF7518;
            color: white;
            border: 2px solid white;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.4);
            /* Deepened shadow on hover */
            transform: translateY(-2px);
        }

        /* Contact Info Section */
        .occasion-contact-info {
            margin-top: 40px;
        }

        .occasion-contact-info p {
            font-size: 0.95em;
            line-height: 1.6;
            margin-bottom: 10px;
            font-weight: 400;
            color: white;
            font-family: 'Roboto', sans-serif;
            /* Regular weight for contact info */
        }

        .occasion-contact-info strong {
            font-weight: 500;
            color: #ffffff;
        }



        .user-info {
            color: #000;
        }

        .booking-form-container {
            width: 100%;

            /* Full viewport height */
            margin: 0 auto;
            padding: 20px;
            background-color: #2C2C2C;
            text-align: center;
            display: none;
            padding: 20px;

        }

        .booking-form-container h2 {
            width: 100%;

            /* Full viewport height */
           
            color: #FF7518;
            
           

        }

        .label-container {
            display: flex;
            padding: 20px;
            justify-content: center;
            background-color: #2C2C2C;
            border-radius: 8px;

        }


        label {


            margin-top: 20px;
            font-size: 1rem;
        }

        .label-content {

            display: flex;

            margin-left: 10px;
            border-radius: 8px;
            justify-content: center;
            color: white;
            gap: 15%;
            border-radius: 5px;

        }

        input[type="date"],
        input[type="text"] {

            margin-top: 10px;
            padding: 10px;
            width: 20%;
            font-size: 1rem;
            border: 1px solid #fff;
            margin-left: 10px;
            color: #000;
            border-radius: 4px;
        }

        select {
            display: flex;
            margin-top: 10px;
            padding: 10px;
            width: 20%;
            font-size: 1rem;
            border: 1px solid #fff;
            color: #000;
            border-radius: 4px;
            margin-left: 10px;
        }

        #time-slots {
            margin-top: 20px;

        }


        .time-slot {
            display: inline-block;
            margin: 5px;
            padding: 10px 20px;
            color: white;
            border: 1px solid #000;
            border-radius: 5px;
            cursor: pointer;
            background-color: #ff6347;

        }


        .time-slot.selected {
            background-color: #ffffff;
            color: #000;
        }

        #reserve-now {
            margin-top: 20px;
            margin-bottom: 20px;
            padding: 10px 30px;
            background-color: #ff6347;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.1rem;
        }

        #reserve-now:hover {
            background-color: #ffffff;
            color: #000;
        }



        .cart-modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgb(0, 0, 0);
            background-color: rgba(0, 0, 0, 0.4);
            padding-top: 60px;
        }

        .cart-modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
        }

        .cart-close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .cart-close:hover,
        .cart-close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }


        .occasion-book-container {
            text-align: center;
            padding: 40px;
            background-color: #ffffff;
            /* White background for the container */
            border-radius: 10px;
            width: 100%;
            /* Full width */
            max-width: 1400px;
            /* Set max width to 1300px */
            margin: 0 auto;
            /* Center the container with top margin */
            display: none;

        }

        h1 {
            font-size: 36px;
            color: #2c3e50;
            /* Darker, more professional color */
            margin-bottom: 15px;
            font-weight: 700;
            /* Bold font */
        }

        p {
            font-size: 20px;
            /* Font size for the paragraph */
            color: #34495e;
            /* Medium dark color for the paragraph */
            line-height: 1.6;
            /* Improved line spacing */
        }

        .description {
            margin-top: 10%;
            /* Top margin of 10% */
            max-width: 600px;
            /* Decrease line width */
            margin-left: auto;
            /* Centering adjustments */
            margin-right: auto;
            /* Centering adjustments */
        }

        /* Styles for service content box */
        .service-content {
            display: flex;
            justify-content: center;
            /* Center boxes horizontally */
            margin-top: 40px;
            /* Space above service content */
            flex-wrap: wrap;
            /* Wrap on smaller screens */
            gap: 0;
            /* No gap between boxes */
        }

        .service-box {
            background-color: #f9f9f9;
            /* Light background for the service boxes */
            border: 1px solid #ddd;
            /* Light border */
            border-radius: 8px;
            /* Rounded corners */
            padding: 20px;
            /* Padding inside the boxes */
            flex: 0 1 300px;
            /* Fixed size for boxes */
            height: 400px;
            /* Fixed height for boxes */
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            /* Subtle shadow */
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            /* Enhanced shadow */
            text-align: center;
            /* Center text inside boxes */
            margin: 0;
            /* Remove margin to eliminate gaps */
        }

        .service-box:hover {

            transform: translateY(-5px);
            /* Move the button up slightly */
        }

        .service-box img {
            width: 100%;
            /* Make image take full width of the box */
            height: 200px;
            /* Fixed height for images */
            object-fit: cover;
            /* Cover the box without distortion */
            border-radius: 8px 8px 0 0;
            /* Rounded corners for the image */
        }

        .service-box h4 {
            margin-top: 15px;
            /* Margin for heading */
            color: #2c3e50;
            /* Darker color for the heading */
        }

        .service-box p {
            color: #555;
            /* Lighter color for the paragraph */
            font-size: 16px;
            /* Font size for service description */
            line-height: 1.4;
            /* Line height for better readability */
        }

        /* Styles for the button */
        .start-planning-button {
            display: inline-block;
            /* Center the button */
            margin-top: 30px;
            /* Space above the button */
            padding: 15px 30px;
            /* Padding inside the button */
            font-size: 18px;
            /* Font size for button text */
            color: #fff;
            /* Text color */
            background-color: #FF7518;
            /* Button color */
            border: 2px solid white;
            border-radius: 25px;
            /* Rounded corners */
            text-decoration: none;
            /* No underline */
            transition: background-color 0.3s;
            /* Transition for hover effect */
            cursor: pointer;
        }

        .start-planning-button:hover {
            background-color: white;
            /* Darker shade on hover */
            color: #2C2C2C;
            border: 2px solid #FF7518;
        }

        /* Styles for the form */
        .occasion-form {
            width: 50%;
            /* Set form width to 50% */
            justify-content: center;
            /* Center the form */
            margin: 0 auto;
            /* Auto margin for centering */
            text-align: center;
            /* Center text within the form */
        }

        .booking-form {
            display: none;
            /* Initially hidden */
            margin-top: 40px;
            /* Space above the form */
        }

        .booking-form input,
        .booking-form textarea,
        .booking-form select {
            width: calc(100% - 20px);
            /* Full width for inputs, adjusted for padding */
            padding: 8px;
            /* Reduced padding for a smaller input */
            margin: 5px 0;
            /* Reduced margin for spacing */
            border: 1px solid #ccc;
            /* Light border */
            border-radius: 4px;
            /* Rounded corners */
            font-size: 14px;
            /* Smaller font size */
            box-sizing: border-box;
            /* Include padding and border in element's total width */
        }

        /* Fix dimensions for textarea */
        .booking-form textarea {
            height: 100px;
            /* Fixed height for textarea */
            resize: none;
            /* Prevent resizing */
            width: 100%;
            /* Full width for textarea */
        }

        .booking-form button {
            padding: 10px 20px;
            /* Reduced padding inside the button */
            font-size: 16px;
            /* Smaller font size for button text */
            color: #fff;
            /* Text color */
            background-color: #FF7518;
            /* Button color */
            border: none;
            /* No border */
            border-radius: 25px;
            /* Rounded corners */
            cursor: pointer;
            /* Pointer cursor */
            transition: background-color 0.3s;
            /* Transition for hover effect */
            display: block;
            /* Block display to center */
            margin: 20px auto;
            /* Center the button */
            margin-bottom: 30px;
            border: 2px solid white;
        }

        .booking-form button:hover {
            background-color: black;
            /* Darker shade on hover */
            color: white;
            border: 2px solid white;
            transform: translateY(-2px);
            font-family: 'Roboto', sans-serif;

        }


        /* .occasion-button {
            background: none;
            border: 2px solid #ffffff;
            color: #ffffff;
            cursor: pointer;
            font-size: 1em;
            display: inline-block;
            padding: 10px 30px;
            background-color: white;
            color: #2C2C2C;
            border: 2px solid #FF7518;
            border-radius: 25px;
            text-decoration: none;
            margin-bottom: 20px;
            font-family: 'Roboto', sans-serif;
            font-weight: 500;
            transition: background-color 0.3s, color 0.3s;
            align-self: start;
            margin-top: 10px;
        }

        .occasion-button:hover {
            background-color: #FF7518;
            color: white;
            border: 2px solid white;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.4);
            transform: translateY(-2px);
        } */








        /* Styles for the form title */
        .form-title {
            font-size: 24px;
            /* Font size for form title */
            color: #2c3e50;
            /* Darker color for the title */
            margin-top: 10px;
            margin-bottom: 20px;
            /* Margin below the title */
            text-align: center;
            /* Center the title */
        }

        /* New container for two boxes */
        .additional-boxes {
            display: flex;
            justify-content: center;
            /* Center the boxes horizontally */
            margin-top: 20px;
            /* Space above the boxes */
            gap: 20px;
            /* Space between the boxes */
        }

        .additional-box {
            background-color: #f9f9f9;
            /* Light background for additional boxes */
            border: 1px solid #ddd;
            /* Light border */
            border-radius: 8px;
            /* Rounded corners */
            padding: 20px;
            /* Padding inside the boxes */
            flex: 0 1 45%;
            /* Adjust size of additional boxes */
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            /* Subtle shadow */
            text-align: center;
            /* Center text inside boxes */
        }

        /* Additional styles for left box with image */
        .additional-box img {
            width: 100%;
            /* Full width for the image */
            height: 200px;
            /* Fixed height for image */
            object-fit: cover;
            /* Cover without distortion */
            border-radius: 8px;
            /* Rounded corners for the image */
            display: block;
            /* Block display for centering */
            margin: 0 auto;
            /* Center the image horizontally */
        }

        .additional-box h4 {
            color: #2c3e50;
            /* Darker color for the heading */
            margin-bottom: 10px;
            /* Margin for heading */
        }

        .additional-box p {
            color: #555;
            /* Lighter color for the paragraph */
            font-size: 16px;
            /* Font size for additional box description */
        }

        /* Right box details styles */
        .right-details {
            text-align: left;
            /* Align text to the left */
        }

        .right-details h4 {
            margin-bottom: 10px;
            /* Margin for heading */
        }

        .right-details p {
            margin: 5px 0;
            /* Margin for paragraph */
        }

        /* Styles for form rows */
        .form-row {
            display: flex;
            justify-content: space-between;
            /* Space out items evenly */
            margin-bottom: 10px;
            /* Margin below the row */
        }

        .form-row input,
        .form-row select {
            width: calc(50% - 10px);
            /* Adjust width to be half of row with some spacing */
        }






.testimonial-section {
    width: 100%;
    max-width: 1400px;
    padding: 2rem;
    color: #ffffff; /* White text color */
    background-color: #2C2C2C; /* Dark gray background color */
    margin: auto;
    font-family: 'Roboto', sans-serif;
    text-align: center;
}

.testimonial-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #444444; /* Light gray border */
    padding-bottom: 1rem;
    margin-bottom: 2rem;
}

.testimonial-header h2 {
    font-size: 2rem;
    font-weight: bold;
    color: #FF7518; /* Theme color */
}

.icons {
    display: flex;
    gap: 1rem;
}

.icon {
    font-size: 1.5rem;
    color: #FF7518; /* Theme color */
}

.testimonials {
    display: flex;
    justify-content: space-around;
    gap: 1rem;
    flex-wrap: wrap; /* Allows responsiveness */
}

.testimonial {
    background-color: #333333; /* Slightly lighter than the body background */
    padding: 1.5rem;
    width: 30%;
    color: white;
    border: 1px solid #444444;
    border-radius: 8px;
    opacity: 0; /* Initial state for animation */
    transform: translateY(30px); /* Start position for animation */
    animation: fadeInUp 1s ease forwards;
    transition: transform 0.3s, box-shadow 0.3s;
    margin-bottom: 1rem; /* Space between rows when wrapping */
}

.testimonial:hover {
    box-shadow: 0 4px 20px rgba(255, 117, 24, 0.3); 
    transform: translateY(-10px); /* Slight lift effect */
}

.testimonial p {
    margin-bottom: 0.5rem;
    color: white;
}

.testimonial .author {
    font-weight: bold;
    color: #FF7518; /* Theme color for author */
}

/* Fade-in Animation */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}


/* Footer Styles */
.footer {
    background-color: #2C2C2C; /* Dark gray theme background */
    color: #ffffff;
    font-family: 'Roboto', sans-serif;
    padding: 2rem;
    text-align: center;
}

footer { box-shadow: 0 -4px 6px rgba(0, 0, 0, 0.1); }

.footer-content {
    display: flex;
    justify-content: space-between;
    gap: 2rem;
    flex-wrap: wrap;
    max-width: 1200px;
    margin: auto;
    padding-bottom: 1rem;
    border-bottom: 1px solid #444444;
}

.footer-section {
    flex: 1;
    min-width: 200px;
    margin-bottom: 1rem;
}

.footer-section h3 {
    font-size: 1.2rem;
    color: #FF7518; /* Theme color for section titles */
    margin-bottom: 0.5rem;
}

.footer-section p,
.footer-section ul,
.footer-section a {
    font-size: 0.9rem;
    color: #ffffff;
}

.footer-section ul {
    list-style: none;
    padding: 0;
}

.footer-section ul li {
    margin: 0.3rem 0;
}

.footer-section a {
    color: #ffffff;
    text-decoration: none;
    transition: color 0.3s ease;
}

.footer-section a:hover {
    color: #FF7518; /* Hover color in theme */
}

/* Social Media Icons */
.social-icons {
    display: flex;
    justify-content: center;
    gap: 1rem;
}

.icon {
    font-size: 1.5rem;
    color: #FF7518; /* Theme color for social media icons */
    transition: transform 0.3s ease;
}

.icon:hover {
    transform: scale(1.1); /* Slight scaling on hover */
    color: #ffffff; /* Changes to white on hover */
}

/* Footer Bottom */
.footer-bottom {
    padding-top: 1rem;
    font-size: 0.8rem;
    color: #888888;
}

.footer-bottom p {
    margin: 0;
}

/* Responsive Footer Layout */
@media (max-width: 768px) {
    .footer-content {
        flex-direction: column;
        align-items: center;
    }

    .footer-section {
        text-align: center;
    }

    .social-icons {
        justify-content: center;
    }
}



     
    </style>
</head>

<body>


    <nav>

        <div class="hamburger" id="hamburgerMenu">
            <div></div>
            <div></div>
            <div></div>
        </div>

        <h1>Bistrofy</h1>

        <div class="nav-links">
            <ul>
                <li><a href="index.php?page=home">Home</a></li>
                <li id="navAboutbtn"><a href="#">About</a></li>
                <li id="navMenubtn"><a href="#">Menu</a></li>
                <li id="navBookingbtn"><a href="#">Book</a></li>
               

                <li>
                    <a id="loginButton">Login</a>
                    <div class="dropdown" id="loginDropdown">
                        <a href="login.php">Sign In</a>
                        <a href="register.php">Register</a>
                        <?php if (isset($_SESSION['user'])): ?>
                            <a href="logout.php">Logout</a>
                        <?php endif; ?>
                    </div>
                </li>
                <li>
                    <span class="cart-icon" id="cart-icon" onclick="toggleCart()"> <i class="fas fa-shopping-cart"></i></span> <!-- Cart Icon -->
                </li>
            </ul>
        </div>

        <div class="profile-container" id="profile-content">
            <img src="https://cdn.vectorstock.com/i/500p/96/75/gray-scale-male-character-profile-picture-vector-51589675.jpg" alt="Profile Picture" class="profile-picture">
            <div class="dropdown" id="profileDropdown">
                <div class="nav-links">
                    <div class="notification-icon">🔔</div>
                    <div class="dark-mode-toggle">🌙</div>
                </div>
                <hr style="border: 1px solid grey; margin: 2px 0;">
                <a href="Profile.php" id="profile-page"> View Profile</a>
                <a href="#">Order History</a>
                <a href="#">My Reservations</a>
                <?php if (isset($_SESSION['user'])): ?>
                    <a href="logout.php">Logout</a>
                <?php endif; ?>
            </div>
        </div>

    </nav>


    <section id="hero">
        <div class="overlay"></div>
        <div class="hero-content">
            <h1>Welcome to Bistrofy</h1>
            <p>A Symphony of Flavors Awaits</p>
            <a href="#" class="btn-primary" id="orderOnlineBtn">Order Online</a>
        </div>
        <img src="featurepic2.png" class="feature-img2">
        <img src="featurepic1.jpg" class="feature-img1">
        <img src="curlyline.jpg" class="feature-img3">
        <img src="curlyline.jpg" class="feature-img4">
    </section>

    <div id="orderContent"></div>
    <div id="navMenuContent"></div>
    <div id="aboutContent"></div>
    <div id="Occasion-Content"></div>
    





    <section class="Bistrofy-container">
        <main>
            <h1 class="Bistrofy-header">Inspired by the rich heritage of Indian cuisine, Bistrofy offers a vibrant, contemporary twist on beloved traditional flavors.</h1>
            <a href="#" class="Bistrofy-book-button" id="bookOnlineBtn">Book a Table</a>
            <img src="dining1.jpg" class="Bistrofy-dining">
        </main>
    </section>


    <div class="booking-form-container" id="book-section">
        <?php if (isset($_SESSION['user'])): ?>
            <div class="user-info">
                <h3>Hello, <?php echo htmlspecialchars($_SESSION['user']['username']); ?>!</h3>
                <p>You are logged in as a <?php echo htmlspecialchars($_SESSION['user']['role']); ?>.</p>
            </div>
        <?php else: ?>
            <p>You are not logged in.</p>
        <?php endif; ?>
        <h2>To help us find the best table for you, select the preferred party size, date, and time of your reservation.</h2>



        <form id="booking-form" method="POST" action="">
            <div class="label-content">
                <label for="event-type">Event</label>
                <label for="reservation-date">Date</label>
                <label for="slots">Slots</label>
            </div>

            <div class="label-container">
                <select id="event-type" name="event_type" required>
                    <option value="" selected disabled hidden>Select an Event Type</option>
                    <option value="birthday">Birthday</option>
                    <option value="wedding">Wedding</option>
                    <option value="corporate">Corporate Event</option>
                    <option value="anniversary">Anniversary</option>
                    <option value="baby-shower">Baby Shower</option>
                    <option value="other">Other</option>
                </select>

                <input type="date" id="reservation_date" name="reservation_date" required>

                <select id="slots" name="slots" required>
                    <option value="1" selected disabled hidden>Select given slots</option>
                    <?php for ($hour = 1; $hour < 24; $hour += 2): ?>
                        <?php
                        $end_hour = ($hour + 2) % 24; // Wrap around to 00:00 after 23:00
                        ?>
                        <option value="<?php echo str_pad($hour, 2, '0', STR_PAD_LEFT); ?>:00 - <?php echo str_pad($end_hour, 2, '0', STR_PAD_LEFT); ?>:00">
                            <?php echo str_pad($hour, 2, '0', STR_PAD_LEFT); ?>:00 - <?php echo str_pad($end_hour, 2, '0', STR_PAD_LEFT); ?>:00
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="label-content" style="margin-left:4%">
                <label for="party-size">Party Size</label>
                <label for="special-requests">Special Request</label>
            </div>

            <div class="label-container">
                <select id="party-size" name="party_size" required>
                    <option value="" selected disabled hidden>Select no. of guests</option>
                    <option value="2">2 guests</option>
                    <option value="3">3 guests</option>
                    <option value="4">4 guests</option>
                    <option value="5">5 guests</option>
                    <option value="6">6 guests</option>
                    <option value="7">7 guests</option>
                    <option value="8">8 guests</option>
                    <option value="9">9 guests</option>
                    <option value="10+">10+ guests</option>
                </select>

                <input type="text" id="special-requests" name="special_requests" rows="1" placeholder="Let us know any special arrangements or requests.">
            </div>

            <input type="checkbox" id="agree" name="agree" required>
            <label for="agree">I agree to the <a href="terms.html">terms and conditions</a>.</label><br>

            <button id="reserve-now" type="submit">RESERVE NOW</button>
        </form>

        <div id="success-message"></div>
        <div id="error-message"></div>

    </div>


    <div id="cartModal" class="cart-modal" style="display:none;">
        <div class="cart-modal-content">
            <span class="cart-close" onclick="toggleCart()">&times;</span>
            <div style="display: flex;color:#000;font-family:'Roboto', sans-serif;">
                <p style=" margin-left:2%;margin-top:3%">My Cart</p>
                <p style=" margin-left:44%;margin-top:3%">Order summary</p>
            </div>


            <?php
            echo "<script>

    function decreaseItemQuantity(button) {
        const quantityInput = button.nextElementSibling;
        let quantity = parseInt(quantityInput.value);
        if (quantity > 1) {
            quantity--;
            quantityInput.value = quantity;
            updateItemTotalPrice(quantityInput);
        }
    }

    function increaseItemQuantity(button) {
        const quantityInput = button.previousElementSibling;
        let quantity = parseInt(quantityInput.value);
        quantity++;
        quantityInput.value = quantity;
        updateItemTotalPrice(quantityInput);
    }

    function updateItemTotalPrice(input) {
        const cartItem = input.closest('.cart-item');
        const itemPrice = parseFloat(cartItem.querySelector('.item-price-single').textContent);
        const quantity = parseInt(input.value);
        const totalPriceElement = cartItem.querySelector('.item-price-total p');

        const newTotalPrice = (itemPrice * quantity).toFixed(2);
        totalPriceElement.textContent = newTotalPrice;

        updateOrderSummary();
    }

    function updateOrderSummary() {
        let subtotal = 0;
        const totalPriceElements = document.querySelectorAll('.item-price-total p');
        
        totalPriceElements.forEach((priceElement) => {
            subtotal += parseFloat(priceElement.textContent);
        });
        
        document.querySelector('.order-summary .summary-item span:last-child').textContent = subtotal.toFixed(2);
}
</script>";
            ?>
            <div class="container" id="cartContainer">

                <!-- Cart items will be displayed here dynamically -->

               
            </div>
        </div>
    </div>


    <div class="occasion-container">
        <div class="occasion-image-section">
            <img src="Occasion1.jpg" alt="Food image">
        </div>
        <div class="occasion-text-section">
            <div class="occasion-icons">
                <!-- Add icons here if you have SVGs or use icon libraries like Font Awesome -->
            </div>
            <h2>Your Perfect Destination for Any Occasion</h2>
            <p class="occasion-description">I'm a paragraph. Click here to add your own text and edit me. I’m a great place for you to tell a story and let your users know a little more about you.</p>
            <button class="occasion-button">Private Events</button>
            <div class="occasion-contact-info">

                <div style="display: flex; align-items: flex-start;">
                    <strong style="margin-right: 10px;">Address:</strong>
                    <p style="margin-left: 86px;">500 Terry Francine Street<br>San Francisco, CA 94158</p>
                </div>
                <div style="display: flex; align-items: flex-start;">
                <strong style="margin-right: 10px;">Opening Hours:</strong>
                <p style="margin-left: 40px;">Mon - Fri : 8am - 8pm<br>Saturday : 9am - 7pm<br>Sunday : 9am - 8pm</p>
                </div>
            </div>
        </div>
    </div>




    <div class="occasion-book-container" id="main-content">
        <h1>Are you planning an event?</h1>
        <p class="description">Explore our private dining experience. We offer various menus tailored to your occasion and budget, from corporate events to private gatherings.</p>

        <div class="service-content">
            <div class="service-box">
                <img src="corporateevent.jpg" alt="Corporate Events">
                <h4>Corporate Events</h4>
                <p>Professional catering services for your corporate needs, ensuring a successful event.</p>
            </div>
            <div class="service-box">
                <img src="privategathering.jpg" alt="Private Gatherings">
                <h4>Private Gatherings</h4>
                <p>Intimate catering solutions for personal celebrations and gatherings.</p>
            </div>
            <div class="service-box">
                <img src="weedingevent.jpg" alt="Weddings">
                <h4>Weddings</h4>
                <p>Elegant catering services to make your wedding day unforgettable with exquisite cuisine.</p>
            </div>
        </div>

        <a href="#" class="start-planning-button">Start Planning</a>
    </div>



    <div class="container booking-form" id="booking-form">

        <h2 class="form-title">Contact</h2>
        <div class="additional-boxes">
            <div class="additional-box">
                <img src="Eventpic.jpg" alt="Get in Touch">
                <h4>Get in Touch</h4>
                <p>Have questions? Reach out to our team for assistance.</p>
            </div>
            <div class="additional-box right-details">
                <h3 style="margin-bottom: 4px;">Find Us</h3>
                <div style="display: flex; align-items: flex-start;">
                <strong style="margin-right: 10px;">Address</strong>
                <p style="margin-left: 86px;">500 Terry Francine Street<br>San Francisco, CA 94158</p>
                </div>

                <div style="display: flex; align-items: flex-start;">
                <strong>Opening Hours</strong style="margin-right: 7px;">
                <p style="margin-left: 46px;">Mon - Fri: 8am - 8pm<br>Saturday: 9am - 7pm<br>Sunday: 9am - 8pm</p>
                </div>
                <div style="display: flex; align-items: flex-start;">
                <strong style="margin-right: 7px;">Phone</strong>
                <p style="margin-left: 107px;">935-456-7890</p>
                </div>
                <div style="display: flex; align-items: flex-start;">
                <strong style="margin-right: 7px;">Email</strong>
                <p style="margin-left: 115px;">info@mysite.com</p>
                </div>
            </div>
        </div>


      


        <form id="form" method="POST" action="" class="occasion-form">
            <h2 class="form-title">Get in Touch</h2>
            <div class="form-row">
                <input type="text" name="first_name" placeholder="First Name*" required>
                <input type="text" name="last_name" placeholder="Last Name*" required>
            </div>
            <div class="form-row">
                <input type="email" name="email" placeholder="Email*" required>
                <input type="tel" name="phone" placeholder="Phone">
            </div>
            <div class="form-row">
                <select name="event_type" required>
                    <option value="private">Private Event</option>
                    <option value="corporate">Corporate Event</option>
                </select>
                <input type="date" name="event_date" required>
            </div>
            <textarea name="message" placeholder="Write a message" required></textarea>
            <button type="submit">Submit</button>

            <?php if (!empty($success_message)) : ?>
                <div class="success-message"><?php echo $success_message; ?></div>
            <?php elseif (!empty($error_message)) : ?>
                <div class="error-message"><?php echo $error_message; ?></div>
            <?php endif; ?>

        </form>

    </div>


    <section class="testimonial-section" id="testimonial">
    <div class="testimonial-header">
        <h2>What Our Diners Say About Us</h2>
        <div class="icons">
            <!-- Replace with actual icons or use Font Awesome, etc. -->
            <span class="icon">🏠</span>
            <span class="icon">🐟</span>
            <span class="icon">🐚</span>
        </div>
    </div>
    <div class="testimonials">
        <div class="testimonial">
            <p>"I'm a testimonial. Click to edit me and add text that says something nice about you and your services."</p>
            <p class="author">Fay Salinas</p>
        </div>
        <div class="testimonial">
            <p>"I'm a testimonial. Click to edit me and add text that says something nice about you and your services. Let your customers review you and tell their friends how great you are."</p>
            <p class="author">Erika Huff</p>
        </div>
        <div class="testimonial">
            <p>"I'm a testimonial. Click to edit me and add text that says something nice about you and your services."</p>
            <p class="author">Zakir Gregory</p>
        </div>
    </div>
</section>


<footer class="footer">
    <div class="footer-content">
        <!-- Contact Information -->
        <div class="footer-section contact-info">
            <h3>Contact Us</h3>
            <p>123 Foodie Lane, Flavor Town</p>
            <p>Phone: (123) 456-7890</p>
            <p>Email: info@yourrestro.com</p>
        </div>

        <!-- Quick Links -->
        <div class="footer-section quick-links">
            <h3>Quick Links</h3>
            <ul>
                <li><a href="#home">Home</a></li>
                <li><a href="#menu">Menu</a></li>
                <li><a href="#about">About Us</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>
        </div>

        <!-- Social Media -->
        <div class="footer-section social-media">
            <h3>Follow Us</h3>
            <div class="social-icons">
                <!-- Replace with actual icons or use Font Awesome if desired -->
                <a href="#" class="icon">📘</a>
                <a href="#" class="icon">📷</a>
                <a href="#" class="icon">🐦</a>
                <a href="#" class="icon">📍</a>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <p style="font-size: 1rem;">&copy; 2024 Bistrofy. All rights reserved.</p>
    </div>
</footer>



    <script>
        $(document).ready(function() {
            $('#orderOnlineBtn').click(function(e) {
                e.preventDefault(); 
            <?php if (isset($_SESSION['user'])) { ?>
                $('#hero').hide();
                $('.Bistrofy-container').hide();
                $('.occasion-container').hide();
                $('#main-content').hide();
                $('#testimonial').hide();
                $('#aboutContent').hide();
                $('#navMenuContent').hide();
                
                
                $('#orderContent').load('order.php', function(response, status, xhr) {
                    if (status == "error") {
                        alert("Error loading page: " + xhr.status + " " + xhr.statusText);
                    }
                });
                <?php } else { ?>

                alert('You must be logged in to proceed!');
                    window.location.href = 'login.php';
                <?php } ?>

            });
        });

        $(document).ready(function() {
            $('#navMenubtn').click(function(e) {
                e.preventDefault(); 
            <?php if (isset($_SESSION['user'])) { ?>
                $('#hero').hide();
                $('.Bistrofy-container').hide();
                $('.occasion-container').hide();
                $('#main-content').hide();
                $('#testimonial').hide();
                $('#aboutContent').hide();
                $('#orderContent').hide();
                
                
                $('#navMenuContent').load('order.php', function(response, status, xhr) {
                    if (status == "error") {
                        alert("Error loading page: " + xhr.status + " " + xhr.statusText);
                    }
                });
                <?php } else { ?>

                alert('You must be logged in to proceed!');
                    window.location.href = 'login.php';
                <?php } ?>

            });
        });



        $(document).ready(function() {
            $('#navAboutbtn').click(function(e) {
                e.preventDefault(); 
            <?php if (isset($_SESSION['user'])) { ?>
                $('#hero').hide();
                $('.Bistrofy-container').hide();
                $('.occasion-container').hide();
                $('#main-content').hide();
                $('#testimonial').hide();
                $('#orderContent').hide();

                
                
                $('#aboutContent').load('About.php', function(response, status, xhr) {
                    if (status == "error") {
                        alert("Error loading page: " + xhr.status + " " + xhr.statusText);
                    }
                });
                <?php } else { ?>

                alert('You must be logged in to proceed!');
                    window.location.href = 'login.php';
                <?php } ?>

            });
        });


     


      

        $(document).ready(function() {
            $('.occasion-button').click(function(e) {
                e.preventDefault(); // Prevent default anchor behavior

                <?php if (isset($_SESSION['user'])) { ?>
                    $('#hero').hide();
                    $('.Bistrofy-container').hide();
                    $('.occasion-container').hide();
                    $('#testimonial').hide();
                    // Use AJAX to load the content of order.php into the #orderContent div
                    $('#main-content').show();
                    $('#booking-form').show();

                <?php } else { ?>
                    alert('You must be logged in to proceed!');
                    window.location.href = 'login.php';
                <?php } ?>

            });
        });



        $(document).ready(function() {
            $('.Bistrofy-book-button').click(function(event) {
                event.preventDefault();

                // Use AJAX to check if the user is logged in before proceeding (optional)
                <?php if (isset($_SESSION['user'])) { ?>
                    $('#hero').hide();
                    $('.Bistrofy-container').hide();
                    $('#testimonial').hide();
                    $('#user-data').hide();
                    $('#aboutContent').hide();
                    $('#book-section').show();
                <?php } else { ?>
                    alert('You must be logged in to proceed!');
                    window.location.href = 'login.php';
                <?php } ?>
            });
        });

        $(document).ready(function() {
            $('#navBookingbtn').click(function(event) {
                event.preventDefault();

                // Use AJAX to check if the user is logged in before proceeding (optional)
                <?php if (isset($_SESSION['user'])) { ?>
                    $('#hero').hide();
                    $('.Bistrofy-container').hide();
                    $('.occasion-container').hide();
                    $('#testimonial').hide();
                    $('#user-data').hide();
                    $('#book-section').show();
                    $('#navMenuContent').hide();

                <?php } else { ?>
                    alert('You must be logged in to proceed!');
                    window.location.href = 'login.php';
                <?php } ?>
            });
        });


        $(document).ready(function() {
            $('.start-planning-button').on('click', function(event) {
                event.preventDefault(); 
                $('.occasion-book-container').hide();
                $('#testimonial').hide();
                $('.booking-form').show(); 
            });

            // Optionally, handle form submission
            $('#form').on('submit', function(event) {
                event.preventDefault(); // Prevent normal form submission
                $.post('', $(this).serialize(), function(data) {
                    alert("Your booking details have been submitted successfully!");
                    $('#form')[0].reset(); // Reset the form fields
                    $('#booking-form').html(data); // Update form content
                });
            });
        });



        $(document).ready(function() {
            $('#booking-form').on('submit', function(event) {
                event.preventDefault(); // Prevent the default form submission

                // Gather form data
                var formData = $(this).serialize() + '&ajax=1'; // Include an AJAX flag

                // Make AJAX request
                $.ajax({
                    type: 'POST',
                    url: '', // URL of the PHP script
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        // Clear previous messages
                        $('#success-message').html('');
                        $('#error-message').html('');

                        if (response.success) {
                            // Show success message
                            $('#success-message').html(response.message);
                        } else {
                            // Show error messages
                            if (response.errors.length > 0) {
                                var errorHtml = '<h2>Errors:</h2><ul>';
                                $.each(response.errors, function(index, error) {
                                    errorHtml += '<li>' + error + '</li>';
                                });
                                errorHtml += '</ul>';
                                $('#error-message').html(errorHtml);
                            }
                        }
                    },
                    error: function() {
                        $('#error-message').html('<h2>An unexpected error occurred. Please try again.</h2>');
                    }
                });
            });
        });








        // Dark mode toggle functionality
        const darkModeToggle = document.querySelector('.dark-mode-toggle');
        darkModeToggle.addEventListener('click', () => {
            document.body.classList.toggle('dark-mode');
            document.querySelector('nav').classList.toggle('dark-mode');
        });

       // Toggle dropdown for profile picture
const profilePicture = document.querySelector('.profile-picture');
const profileDropdown = document.querySelector('#profileDropdown');

// Toggle dropdown visibility on profile picture click
profilePicture.addEventListener('click', (event) => {
    profileDropdown.style.display = profileDropdown.style.display === 'block' ? 'none' : 'block';
    event.stopPropagation(); // Prevent click from propagating to document
});

// Hide dropdown when clicking outside of it
document.addEventListener('click', (event) => {
    if (!profileDropdown.contains(event.target) && !profilePicture.contains(event.target)) {
        profileDropdown.style.display = 'none';
    }
});


        // Toggle dropdown for login button
        const loginButton = document.querySelector('#loginButton');
        const loginDropdown = document.querySelector('#loginDropdown');

        loginButton.addEventListener('click', () => {
            loginDropdown.style.display = loginDropdown.style.display === 'block' ? 'none' : 'block';
        });



// Hide dropdowns when clicking outside of them
document.addEventListener('click', (event) => {
    // Close login dropdown if clicked outside
    if (!loginDropdown.contains(event.target) && !loginButton.contains(event.target)) {
        loginDropdown.style.display = 'none';
    }
});




        const hamburgerMenu = document.getElementById('hamburgerMenu');
        const nav = document.querySelector('nav');

        hamburgerMenu.addEventListener('click', () => {
            nav.classList.toggle('active');
        });



        $(document).ready(function() {
            $('#loginButton').click(function() {
                $('#dropdown').show();
            });
        });



        // script.js

        // Function to toggle the cart modal
        function toggleCart() {
            const cartModal = document.getElementById("cartModal");
            if (cartModal.style.display === "block") {
                cartModal.style.display = "none";
            } else {
                cartModal.style.display = "block";
                loadCartItems(); // Load cart items when opening the cart
            }
        }

        // Function to load cart items from the server
        function loadCartItems() {
            fetch('add_to_cart.php') // Change to your actual cart page URL
                .then(response => response.text())
                .then(data => {
                    document.getElementById('cartContainer').innerHTML = data;
                })
                .catch(error => {
                    console.error('Error loading cart items:', error);
                });
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            const modal = document.getElementById("cartModal");
            if (event.target === modal) {
                modal.style.display = "none";
            }
        }


        function deleteCartItem(cartItemId) {
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "add_to_cart.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                const response = xhr.responseText; // Get the response as plain text
                alert(response); // Show the response message
                if (response.includes('deleted successfully')) {
                    location.reload(); // Reload the page to reflect the changes
                }
            }
        };
        xhr.send("delete_item_id=" + cartItemId);
    }
        
    </script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

</body>

</html>