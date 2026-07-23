<?php
/**
 * Database Migration - Update Campus Locations and Coordinates
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../backend/db.php';

try {
    $locations = [
        ["Main Gate", "The primary entry and exit gate of Saranathan College of Engineering on the Trichy-Madurai Highway.", "Highway Entrance"],
        ["Main Parking", "Secure parking space for two-wheelers and four-wheelers located near the entrance.", "Near Entrance"],
        ["Football Ground", "Dedicated turf for football tournaments and training.", "Near Entrance"],
        ["Main Ground", "Large open playground for cricket, track events, and annual collegiate sports events.", "West Side"],
        ["KS Block", "Academic block containing classrooms and laboratories for EEE, ECE, and ICE departments.", "Block A"],
        ["RV Block", "Academic block housing class sessions, advanced programming labs for CSE and IT departments.", "Block B"],
        ["JS Block", "Academic building housing classrooms and research labs for Civil Engineering and AI&DS.", "JS Block"],
        ["Canteen", "Serves vegetarian meals, hot snacks, tea, and beverages.", "Groundfloor"],
        ["BD Block", "Academic building housing classrooms for MBA, Science & Humanities.", "BD Block"],
        ["Girls' Mess", "Exclusive vegetarian dining hall facility for hostel students.", "Near Hostel"],
        ["Mechanical Block", "Dedicated to the Mechanical Engineering department, housing thermodynamic and CAD labs.", "Mech Block"],
        ["Bus Parking", "Transit zone where college buses arrive and park, connecting students across Trichy.", "Depot"],
        ["Boys' Hostel", "Residential hostels for male students with study halls and mess operations.", "North Side"],
        ["Tennis Ground", "Standard outdoor tennis courts for recreation and training.", "Near KS Block"]
    ];

    foreach ($locations as $loc) {
        $stmt = $db->prepare("INSERT INTO campus_locations (name, description, location_details) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE description = ?, location_details = ?");
        $stmt->execute([$loc[0], $loc[1], $loc[2], $loc[1], $loc[2]]);
    }

    echo json_encode(["status" => "success", "message" => "All 14 campus locations have been successfully updated/inserted."]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
