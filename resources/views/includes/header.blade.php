<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Integration Center</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('assets/img/favicon.png') }}" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f0f0f0; /* Background color for contrast */
        }

        .container {
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .container2 {
            padding: 20px;
        }

        .row {
            text-align: center; /* Center-aligns the contents of the row */
        }

        .logo-wrapper {
            opacity: 0;
            transition: opacity 2s ease-in-out;
            margin: 0 10px; /* Space between logos */
            position: relative; /* Position relative to position the pseudo-element */
            display: inline-block; /* Ensure wrapper fits the content */
        }

        .sync-branch-button, .sync-sara-button {
            opacity: 0;
            animation: fadeIn 1s 0.5s forwards;
        }

        #toyou-wrapper {
            animation: fadeIn 1s forwards;
        }

        #ninja-wrapper {
            animation: fadeIn 1s 1s forwards; /* Delay for the second logo */
        }
        #keta-wrapper {
            animation: fadeIn 1s 2s forwards; /* Delay for the second logo */
        }
        #LCP-wrapper {
            animation: fadeIn 0.3s forwards; /* Shorter duration for a quicker fade-in */
        }

        #psk-wrapper {
            animation: fadeIn 0.3s 0.5s forwards; /* Delay for the second logo */
        }

        #CND-wrapper {
            animation: fadeIn 0.3s 1s forwards; /* Delay for the third logo */
        }

        #Okashi-wrapper {
            animation: fadeIn 0.3s 1.5s forwards; /* Delay for the fourth logo */
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo {
            width: 100%; /* Make image responsive */
            max-width: 300px; /* Limit the max width of the image */
            height: auto; /* Maintain aspect ratio */
            border-radius: 15px; /* Border radius for rounded corners */
            display: block; /* Ensures that margins and padding are applied correctly */
            position: relative; /* Position relative to ensure pseudo-element is aligned properly */
            z-index: 1; /* Ensure the image is above the pseudo-element */
        }
        .logo h1 {
            white-space: nowrap;
            font-weight: bolder;
            color: #666a6e;
        }

        .logo-wrapper::before {
            content: '';
            position: absolute;
            top: -5px; /* Adjust to fit border width */
            left: -5px; /* Adjust to fit border width */
            right: -5px; /* Adjust to fit border width */
            bottom: -5px; /* Adjust to fit border width */
            background: linear-gradient(45deg, #cde2ff4d, #f0f0f0); /* Gradient border */
            border-radius: 20px; /* Adjust to be larger than the image's border-radius */
            z-index: -1; /* Place the pseudo-element behind the image */
            display: block;
        }

        /* Button styles */
        button {
            padding: 10px 50px; /* Padding inside the button */
            border-radius: 12px; /* Rounded corners for the button */
            border: none; /* Remove default border */
            background-color: #337ab7; /* Background color */
            color: white; /* Text color */
            font-size: 16px; /* Font size */
            cursor: pointer; /* Pointer cursor on hover */
            transition: background-color 0.3s, transform 0.2s; /* Smooth transitions */
            margin-bottom: 10px;
            font-weight: bolder;
        }

        button:hover {
            background-color: #0056b3; /* Darker background on hover */
            transform: scale(1.05); /* Slightly enlarge the button */
        }

        button:focus {
            outline: none; /* Remove default focus outline */
        }
        .top-image {
            max-width: 25%; /* Make sure the image is responsive */
            height: auto; /* Maintain aspect ratio */
            margin-bottom: 70px; /* Space below the image */
            opacity: 0;
            transform: translateY(-20px);
            transition: opacity 1s ease-in-out, transform 1s ease-in-out;
        }
        .top-image.loaded {
            opacity: 1;
            transform: translateY(0);
        }
        .navbar {
            background-color: #ebeef2; /* Navbar background color */
            padding: 10px 0; /* Padding for top and bottom */
            position: fixed; /* Fix the navbar at the top */
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000; /* Ensure it's above other content */
        }

        .navbar .container {
            display: flex;
            justify-content: center;
        }

        .navbar-nav {
            list-style: none; /* Remove list styles */
            padding: 0;
            margin: 0;
            display: flex; /* Align items in a row */
        }

        .navbar-nav li {
            margin: 0 15px; /* Space between items */
        }

        .navbar-nav a {
            color: #666a6e; /* Text color */
            text-decoration: none; /* Remove underline */
            font-weight: bold; /* Bold text */
            font-size: 16px; /* Font size */
        }

        .navbar-nav a:hover {
            text-decoration: underline; /* Underline on hover */
        }

        /* Adjust content padding for fixed navbar */
        body {
            padding-top: 60px; /* Space for the fixed navbar */
        }

        /* Media Query for extra-small screens */
        @media (max-width: 767px) {
            .col-xs-12 {
                padding-top: 20px; /* Add top padding for extra-small screens */
            }
            .top-image2 {
                margin-top: 400px; /* Adjust as needed */
                display: block;
                margin-left: auto;
                margin-right: auto;
            }
        }
    </style>
</head>
<body>

