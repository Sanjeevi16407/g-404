<?php
/**
 * Database Migration Script
 * Aligns the campus library location to JS Block Groundfloor in buddy_knowledge and campus_locations tables.
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../backend/db.php';

$response = [
    "success" => false,
    "messages" => []
];

try {
    // 1. Insert or update the library entry in buddy_knowledge (FAQ)
    $answer = "The college library is located in the JS Block Groundfloor.";
    $keywords = "library, where is library, campus library, library location, library block, library path";
    
    // Check if an entry already exists for library questions
    $check_stmt = $db->prepare("SELECT id FROM buddy_knowledge WHERE question_keywords LIKE '%library%' LIMIT 1");
    $check_stmt->execute();
    $knowledge_id = $check_stmt->fetchColumn();
    
    if ($knowledge_id) {
        $update_stmt = $db->prepare("UPDATE buddy_knowledge SET answer = ?, question = ?, question_keywords = ? WHERE id = ?");
        $update_stmt->execute([$answer, "Where is the campus library?", $keywords, $knowledge_id]);
        $response["messages"][] = "Updated existing buddy_knowledge entry (ID: $knowledge_id) to return: '$answer'";
    } else {
        $insert_stmt = $db->prepare("INSERT INTO buddy_knowledge (question, question_keywords, category, answer, priority, status) VALUES (?, ?, 'Library', ?, 'high', 'active')");
        $insert_stmt->execute(["Where is the campus library?", $keywords, $answer]);
        $response["messages"][] = "Created new buddy_knowledge entry to return: '$answer'";
    }
    
    // 2. Update campus_locations table for 'Library'
    $loc_stmt = $db->prepare("SELECT id FROM campus_locations WHERE name LIKE '%library%' LIMIT 1");
    $loc_stmt->execute();
    $loc_id = $loc_stmt->fetchColumn();
    
    if ($loc_id) {
        $update_loc = $db->prepare("UPDATE campus_locations SET location_details = 'JS Block Groundfloor', description = ? WHERE id = ?");
        $update_loc->execute([$answer, $loc_id]);
        $response["messages"][] = "Updated existing campus_locations entry (ID: $loc_id) with new location details.";
    } else {
        $insert_loc = $db->prepare("INSERT INTO campus_locations (name, description, location_details, opening_hours, closing_hours) VALUES ('Library', ?, 'JS Block Groundfloor', '8:30 AM', '6:00 PM')");
        $insert_loc->execute([$answer]);
        $response["messages"][] = "Created new campus_locations entry for 'Library'.";
    }
    
    // 3. Clear gemini_cache to ensure no old responses interfere
    $clear_cache = $db->query("DELETE FROM gemini_cache WHERE query_text LIKE '%library%' OR response_text LIKE '%library%'");
    $response["messages"][] = "Cleared cached library-related queries from gemini_cache.";
    
    $response["success"] = true;
} catch (PDOException $e) {
    $response["messages"][] = "Database error: " . $e->getMessage();
}

echo json_encode($response, JSON_PRETTY_PRINT);
exit;
?>
