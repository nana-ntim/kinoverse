<?php

/**
 * Validation Utilities
 * Common validation functions for authentication processes
 */

// Email Validation
function validateEmail($email) {
    // Remove whitespace
    $email = trim($email);
    
    // Check if empty
    if (empty($email)) {
        return "Email address is required";
    }
    
    // Sanitize and validate format
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return "Please enter a valid email address";
    }
    
    // Check length
    if (strlen($email) > 255) {
        return "Email address is too long";
    }
    
    return null; // No error
}

// Password Validation
function validatePassword($password) {
    if (empty($password)) {
        return "Password is required";
    }
    
    if (strlen($password) < 8) {
        return "Password must be at least 8 characters long";
    }
    
    if (strlen($password) > 255) {
        return "Password exceeds maximum length";
    }
    
    // Check password strength
    if (!preg_match("/[A-Z]/", $password)) {
        return "Password must contain at least one uppercase letter";
    }
    
    if (!preg_match("/[a-z]/", $password)) {
        return "Password must contain at least one lowercase letter";
    }
    
    if (!preg_match("/[0-9]/", $password)) {
        return "Password must contain at least one number";
    }
    
    return null; // No error
}

// Username Validation
function validateUsername($username) {
    // Remove whitespace
    $username = trim($username);
    
    if (empty($username)) {
        return "Username is required";
    }
    
    // Check length (3-50 characters)
    if (strlen($username) < 3) {
        return "Username must be at least 3 characters long";
    }
    
    if (strlen($username) > 50) {
        return "Username cannot exceed 50 characters";
    }
    
    // Only allow letters, numbers, and underscores
    if (!preg_match("/^[a-zA-Z0-9_]+$/", $username)) {
        return "Username can only contain letters, numbers, and underscores";
    }
    
    return null; // No error
}

// Name Validation
function validateName($name, $field = "Name") {
    // Remove whitespace
    $name = trim($name);
    
    if (empty($name)) {
        return "$field is required";
    }
    
    // Check length (2-50 characters)
    if (strlen($name) < 2) {
        return "$field must be at least 2 characters long";
    }
    
    if (strlen($name) > 50) {
        return "$field cannot exceed 50 characters";
    }
    
    // Only allow letters and basic punctuation
    if (!preg_match("/^[a-zA-Z\s\-\']+$/", $name)) {
        return "$field can only contain letters, spaces, hyphens, and apostrophes";
    }
    
    return null; // No error
}

// Generic Required Field Validation
function validateRequired($value, $fieldName) {
    $value = trim($value);
    
    if (empty($value)) {
        return "$fieldName is required";
    }
    
    return null; // No error
}

// Error Handling Helper
function redirectWithError($error, $formData, $redirectPath) {
    $_SESSION['error'] = $error;
    $_SESSION['form_data'] = $formData;
    header("Location: " . $redirectPath);
    exit();
}
?>