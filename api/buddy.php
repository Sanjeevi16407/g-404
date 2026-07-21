<?php
/**
 * Buddy AI Chatbot AJAX API Endpoint (Real-Time Database Context Integrated via RAG)
 * Processes queries, matches local keywords, or queries Gemini model with dynamic context injection.
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../backend/db.php';

// Initialize session conversation history if missing
if (!isset($_SESSION['buddy_history'])) {
    $_SESSION['buddy_history'] = [];
}

// Clear history if requested
if (isset($_GET['clear']) && $_GET['clear'] == 1) {
    $_SESSION['buddy_history'] = [];
    echo json_encode(["status" => "cleared", "answer" => "Conversation history reset successfully.", "suggestions" => []]);
    exit;
}

$response = [
    "answer" => "",
    "category" => "General Knowledge",
    "answered_by" => "Gemini",
    "suggestions" => []
];

// Helper to categorize query locally
function detectCategory($query) {
    $q = strtolower($query);
    if (preg_match('/(bus|transport|pickup|route|driver)/i', $q)) return 'Bus & Transport';
    if (preg_match('/(faculty|teacher|professor|hod|coordinator|cabin|office|mentors)/i', $q)) return 'Faculty Information';
    if (preg_match('/(canteen|food|cafeteria|lunch|breakfast|snack)/i', $q)) return 'Canteen';
    if (preg_match('/(library|book|books|read|journal)/i', $q)) return 'Library';
    if (preg_match('/(hostel|mess|warden|rooms)/i', $q)) return 'Hostel';
    if (preg_match('/(club|clubs|coding|photography|web)/i', $q)) return 'Clubs & Events';
    if (preg_match('/(event|events|symposium|seminar|hackathon)/i', $q)) return 'Clubs & Events';
    if (preg_match('/(timetable|class|period|schedule|hour)/i', $q)) return 'Timetable';
    if (preg_match('/(dept|department|cse|ece|mech|it|eee|civil|science|humanities)/i', $q)) return 'Departments';
    if (preg_match('/(exam|result|attendance|id card|hall ticket|office|fees|services)/i', $q)) return 'Student Services';
    if (preg_match('/(hello|hi|hey|good morning|greetings)/i', $q)) return 'Greetings';
    if (preg_match('/(how are you|buddy|friend|who are you)/i', $q)) return 'Small Talk';
    if (preg_match('/(python|java|c\+\+|javascript|coding|programming|loop|array|function|class)/i', $q)) return 'Programming';
    if (preg_match('/(math|algebra|geometry|calculus|equation|formula)/i', $q)) return 'Mathematics';
    if (preg_match('/(physics|chemistry|biology|quantum|gravity|science)/i', $q)) return 'Science';
    if (preg_match('/(job|career|placement|interview|resume)/i', $q)) return 'Career Guidance';
    return 'General Knowledge';
}

// Helper to fetch static suggestions when DB match or JSON parse fallback
function getStaticSuggestions($category) {
    switch ($category) {
        case 'Library':
            return ["Library timings", "Membership process", "Digital Library", "Reading Hall"];
        case 'Bus & Transport':
            return ["Show bus timing", "Route 5 details", "Bus coordinator contact"];
        case 'Canteen':
            return ["Canteen menu", "Cafeteria location", "Lunch timings"];
        case 'Faculty Information':
            return ["HOD of CSE", "Faculty cabin location", "Faculty email"];
        case 'Hostel':
            return ["Hostel warden number", "Hostel gate timings", "Mess menu"];
        case 'Clubs & Events':
            return ["Show events", "Technical clubs list", "Join Coding Club"];
        case 'Timetable':
            return ["Show timetable", "Lab sessions", "Period timings"];
        case 'Greetings':
        case 'Small Talk':
            return ["Where is library?", "Show bus timing", "Meet my HOD"];
        default:
            return ["Where is library?", "Canteen hours", "Show events"];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $startTime = microtime(true);
    $query = trim($_POST['query'] ?? '');
    
    if (!empty($query)) {
        // Log query event to standard analytics
        try {
            $log_stmt = $db->prepare("INSERT INTO analytics_logs (event_type, item_name) VALUES ('buddy_query', ?)");
            $log_stmt->execute([substr($query, 0, 100)]);
        } catch (PDOException $e) {}

        // Stage 1: Categorize query
        $localCategory = detectCategory($query);

        // Stage 2: Scan Local FAQ Knowledge Base (buddy_knowledge table)
        $found_local_match = false;
        $faq_stmt = $db->query("SELECT * FROM buddy_knowledge WHERE status = 'active'");
        $faqs = $faq_stmt->fetchAll();

        foreach ($faqs as $faq) {
            $keywords = array_map('trim', explode(',', $faq['question_keywords']));
            foreach ($keywords as $kw) {
                if (!empty($kw) && stripos($query, $kw) !== false) {
                    $response["answer"] = $faq['answer'];
                    $response["category"] = $faq['category'] ?: $localCategory;
                    $response["answered_by"] = "Knowledge Base";
                    $response["suggestions"] = getStaticSuggestions($response["category"]);
                    $found_local_match = true;
                    
                    // Add this local round to history to maintain context
                    $_SESSION['buddy_history'][] = [
                        "role" => "user",
                        "parts" => [["text" => $query]]
                    ];
                    $_SESSION['buddy_history'][] = [
                        "role" => "model",
                        "parts" => [["text" => $faq['answer']]]
                    ];
                    
                    // Log metrics
                    try {
                        $responseTime = microtime(true) - $startTime;
                        $metrics_stmt = $db->prepare("INSERT INTO buddy_analytics (question, category, answered_by, response_time) VALUES (?, ?, 'Knowledge Base', ?)");
                        $metrics_stmt->execute([$query, $response["category"], $responseTime]);
                    } catch (PDOException $e) {}
                    
                    break 2;
                }
            }
        }

        // Stage 3: Gemini Fallback with Caching
        if (!$found_local_match) {
            $query_hash = md5(strtolower($query));
            
            // Check Cache first
            $cache_stmt = $db->prepare("SELECT * FROM gemini_cache WHERE query_hash = ? LIMIT 1");
            $cache_stmt->execute([$query_hash]);
            $cached_row = $cache_stmt->fetch();
            
            if ($cached_row) {
                $response["answer"] = $cached_row['response_text'];
                $response["category"] = $cached_row['category'];
                $response["answered_by"] = "Gemini";
                $response["suggestions"] = json_decode($cached_row['suggestions'], true) ?: getStaticSuggestions($cached_row['category']);
                
                // Add to session history
                $_SESSION['buddy_history'][] = [
                    "role" => "user",
                    "parts" => [["text" => $query]]
                ];
                $_SESSION['buddy_history'][] = [
                    "role" => "model",
                    "parts" => [["text" => $cached_row['response_text']]]
                ];
                
                // Keep conversation history capped at last 10 messages
                if (count($_SESSION['buddy_history']) > 10) {
                    $_SESSION['buddy_history'] = array_slice($_SESSION['buddy_history'], -10);
                }
                
                // Log metrics
                try {
                    $responseTime = microtime(true) - $startTime;
                    $metrics_stmt = $db->prepare("INSERT INTO buddy_analytics (question, category, answered_by, response_time) VALUES (?, ?, 'Gemini', ?)");
                    $metrics_stmt->execute([$query, $response["category"], $responseTime]);
                } catch (PDOException $e) {}
                
            } else {
                // Call Gemini
                // Append user message to history
                $_SESSION['buddy_history'][] = [
                    "role" => "user",
                    "parts" => [["text" => $query]]
                ];

                if (count($_SESSION['buddy_history']) > 10) {
                    $_SESSION['buddy_history'] = array_slice($_SESSION['buddy_history'], -10);
                }

                // RAG Context Scraper
                $student_id = $_SESSION['student_id'] ?? 0;
                $student_context = "";
                $timetable_context = "";
                $faculty_context = "";
                $campus_context = "";
                $club_context = "";
                $event_context = "";
                $announcement_context = "";
                
                $dept_id = 0;
                $sec_id = 0;
                
                if ($student_id > 0) {
                    try {
                        $st_stmt = $db->prepare("
                            SELECT s.name, s.register_number, s.email, s.phone, s.department_id, s.section_id, d.name as dept_name, sec.name as section_name
                            FROM students s
                            JOIN departments d ON s.department_id = d.id
                            JOIN sections sec ON s.section_id = sec.id
                            WHERE s.id = ?
                        ");
                        $st_stmt->execute([$student_id]);
                        $st_info = $st_stmt->fetch();
                        if ($st_info) {
                            $dept_id = (int)$st_info['department_id'];
                            $sec_id = (int)$st_info['section_id'];
                            $student_context = "Current Student Details:\n- Name: " . $st_info['name'] . "\n- Register Number: " . $st_info['register_number'] . "\n- Department: " . $st_info['dept_name'] . "\n- Section: " . $st_info['section_name'] . "\n\n";
                        }
                    } catch (PDOException $e) {}
                }
                
                $q_lower = strtolower($query);
                
                // Timetable context
                if (preg_match('/(timetable|class|period|schedule|monday|tuesday|wednesday|thursday|friday|saturday|today|tomorrow)/i', $q_lower)) {
                    if ($dept_id > 0 && $sec_id > 0) {
                        try {
                            $tt_stmt = $db->prepare("
                                SELECT t.day_of_week, t.period_number, t.subject_name, t.room_number, f.name as faculty_name
                                FROM timetable t
                                JOIN faculty f ON t.faculty_id = f.id
                                WHERE t.department_id = ? AND t.section_id = ?
                                ORDER BY FIELD(t.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), t.period_number
                            ");
                            $tt_stmt->execute([$dept_id, $sec_id]);
                            $tt_rows = $tt_stmt->fetchAll();
                            if ($tt_rows) {
                                $timetable_context = "Student's Weekly Period Timetable:\n";
                                foreach ($tt_rows as $r) {
                                    $timetable_context .= "- " . $r['day_of_week'] . " Period " . $r['period_number'] . ": " . $r['subject_name'] . " (Room " . $r['room_number'] . ") by " . $r['faculty_name'] . "\n";
                                }
                                $timetable_context .= "\n";
                            }
                        } catch (PDOException $e) {}
                    }
                }
                
                // Faculty context
                if (preg_match('/(faculty|teacher|professor|coordinator|cabin|meet|who is|email|specialization|natarajan|maths|physics|chemistry|english)/i', $q_lower)) {
                    try {
                        $fac_stmt = $db->query("
                            SELECT f.name, f.designation, f.cabin_location, f.email, f.subject_specialization, d.name as dept_name 
                            FROM faculty f
                            JOIN departments d ON f.department_id = d.id
                        ");
                        $fac_rows = $fac_stmt->fetchAll();
                        if ($fac_rows) {
                            $faculty_context = "Faculty cabin and contact directory:\n";
                            foreach ($fac_rows as $r) {
                                $faculty_context .= "- " . $r['name'] . " (" . $r['designation'] . " in " . $r['dept_name'] . "): Cabin " . $r['cabin_location'] . ", Email: " . $r['email'] . ", Specialization: " . $r['subject_specialization'] . "\n";
                            }
                            $faculty_context .= "\n";
                        }
                    } catch (PDOException $e) {}
                }
                
                // Campus locations
                if (preg_match('/(canteen|library|where is|location|block|campus|how to|map|opening|hours|timing|timings|closed|open)/i', $q_lower)) {
                    try {
                        $loc_stmt = $db->query("SELECT name, description, opening_hours, closing_hours, location_details FROM campus_locations");
                        $loc_rows = $loc_stmt->fetchAll();
                        if ($loc_rows) {
                            $campus_context = "Campus Location Directory & Operating Hours:\n";
                            foreach ($loc_rows as $r) {
                                $campus_context .= "- " . $r['name'] . ": " . $r['description'] . " (Location details: " . $r['location_details'] . "). Timings: " . $r['opening_hours'] . " to " . $r['closing_hours'] . "\n";
                            }
                            $campus_context .= "\n";
                        }
                    } catch (PDOException $e) {}
                }
                
                // Clubs
                if (preg_match('/(club|clubs|join|coordinator|activity|extracurricular|coding|photography|music)/i', $q_lower)) {
                    try {
                        $cl_stmt = $db->query("SELECT name, description, faculty_coordinator FROM clubs");
                        $cl_rows = $cl_stmt->fetchAll();
                        if ($cl_rows) {
                            $club_context = "Clubs Directory:\n";
                            foreach ($cl_rows as $r) {
                                $club_context .= "- " . $r['name'] . ": " . $r['description'] . " (Coordinator: " . $r['faculty_coordinator'] . ")\n";
                            }
                            $club_context .= "\n";
                        }
                    } catch (PDOException $e) {}
                }
                
                // Events
                if (preg_match('/(event|events|symposium|seminar|workshop|cultural|upcoming|register)/i', $q_lower)) {
                    try {
                        $ev_stmt = $db->query("SELECT title, description, venue, event_date, event_time FROM events");
                        $ev_rows = $ev_stmt->fetchAll();
                        if ($ev_rows) {
                            $event_context = "Upcoming Events Schedule:\n";
                            foreach ($ev_rows as $r) {
                                $event_context .= "- " . $r['title'] . ": " . $r['description'] . " at venue " . $r['venue'] . " on " . $r['event_date'] . " " . $r['event_time'] . "\n";
                            }
                            $event_context .= "\n";
                        }
                    } catch (PDOException $e) {}
                }
                
                // Announcements
                if (preg_match('/(announcement|announcements|notice|notices|news|alert|publish)/i', $q_lower)) {
                    try {
                        $ann_stmt = $db->query("SELECT title, description, priority, publish_date FROM announcements ORDER BY publish_date DESC LIMIT 5");
                        $ann_rows = $ann_stmt->fetchAll();
                        if ($ann_rows) {
                            $announcement_context = "Recent Portal Announcements:\n";
                            foreach ($ann_rows as $r) {
                                $announcement_context .= "- [" . $r['publish_date'] . "] (" . $r['priority'] . " priority) " . $r['title'] . ": " . $r['description'] . "\n";
                            }
                            $announcement_context .= "\n";
                        }
                    } catch (PDOException $e) {}
                }
                
                $rag_context = $student_context . $timetable_context . $faculty_context . $campus_context . $club_context . $event_context . $announcement_context;

                // Retrieve settings
                $buddy_settings = $db->query("SELECT gemini_api_key, buddy_name FROM buddy_settings WHERE id = 1 LIMIT 1")->fetch();
                $api_key = $buddy_settings['gemini_api_key'] ?? (defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '');
                $buddy_name = $buddy_settings['buddy_name'] ?? 'Buddy';

                if (!empty($api_key)) {
                    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $api_key;
                    
                    $system_instruction = "You are Buddy, the Digital Senior of Saranathan College of Engineering.\n" .
                                         "Your primary responsibility is to help students.\n" .
                                         "Always answer the user's question whenever possible.\n" .
                                         "If the question is about Saranathan College and verified local information is available, use it.\n" .
                                         "Otherwise provide an accurate and helpful answer using your general knowledge.\n" .
                                         "Never say you cannot answer unless the request is impossible or violates safety rules.\n" .
                                         "Never mention Google Gemini, AI model, or API.\n" .
                                         "Always respond as Buddy.\n" .
                                         "Support English, Tamil, and Tanglish.\n" .
                                         "Maintain a friendly senior-like personality.\n" .
                                         "If appropriate, end with a related suggestion that may help the student.\n\n" .
                                         "IMPORTANT: You MUST format your response as a valid JSON object ONLY. Do NOT wrap it in ```json ... ``` code blocks. Output raw JSON plain text only. The JSON structure MUST contain:\n" .
                                         "{\n" .
                                         "  \"category\": \"Choose one from: Campus Information, Faculty Information, Departments, Clubs & Events, Bus & Transport, Timetable, Hostel, Library, Canteen, Student Services, General Knowledge, Programming, Mathematics, Science, Career Guidance, Greetings, Small Talk\",\n" .
                                         "  \"response\": \"Your conversational answer as Buddy\",\n" .
                                         "  \"suggestions\": [\"array of 3 suggested follow-up questions\"]\n" .
                                         "}";

                    if (!empty($rag_context)) {
                        $system_instruction .= "\n\nUse the following real-time database context from the portal to answer the student's question accurately if relevant:\n" . $rag_context;
                    }

                    $payload = [
                        "contents" => array_values($_SESSION['buddy_history']),
                        "systemInstruction" => [
                            "parts" => [
                                ["text" => $system_instruction]
                            ]
                        ],
                        "generationConfig" => [
                            "maxOutputTokens" => 400,
                            "temperature" => 0.7
                        ]
                    ];

                    // cURL request
                    $ch = curl_init($url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

                    $curl_response = curl_exec($ch);
                    
                    if (curl_errno($ch)) {
                        $response["answer"] = "Buddy says: I'm having trouble reaching my AI service at the moment. I can still help with campus-related questions from my knowledge base.";
                        $response["category"] = $localCategory;
                        $response["suggestions"] = getStaticSuggestions($localCategory);
                        array_pop($_SESSION['buddy_history']);
                    } else {
                        $result = json_decode($curl_response, true);
                        $raw_answer = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
                        
                        // Parse JSON from model
                        $parsed_json = json_decode(trim($raw_answer), true);
                        if ($parsed_json && isset($parsed_json['response'])) {
                            $response["answer"] = trim($parsed_json['response']);
                            $response["category"] = $parsed_json['category'] ?? $localCategory;
                            $response["suggestions"] = $parsed_json['suggestions'] ?? getStaticSuggestions($response["category"]);
                        } else {
                            // Fallback if model didn't return valid JSON
                            $response["answer"] = trim(str_replace('```json', '', str_replace('```', '', $raw_answer)));
                            $response["category"] = $localCategory;
                            $response["suggestions"] = getStaticSuggestions($localCategory);
                        }

                        // Save model turn to history
                        $_SESSION['buddy_history'][] = [
                            "role" => "model",
                            "parts" => [["text" => $response["answer"]]]
                        ];

                        // Cache the response
                        try {
                            $suggestions_json = json_encode($response["suggestions"]);
                            $cache_ins = $db->prepare("INSERT INTO gemini_cache (query_hash, query_text, response_text, category, suggestions) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE response_text = ?, category = ?, suggestions = ?");
                            $cache_ins->execute([$query_hash, $query, $response["answer"], $response["category"], $suggestions_json, $response["answer"], $response["category"], $suggestions_json]);
                        } catch (PDOException $e) {}

                        // Log metrics
                        try {
                            $responseTime = microtime(true) - $startTime;
                            $metrics_stmt = $db->prepare("INSERT INTO buddy_analytics (question, category, answered_by, response_time) VALUES (?, ?, 'Gemini', ?)");
                            $metrics_stmt->execute([$query, $response["category"], $responseTime]);
                        } catch (PDOException $e) {}
                    }
                    curl_close($ch);
                } else {
                    $response["answer"] = "Buddy says: I'm having trouble reaching my AI service at the moment. I can still help with campus-related questions from my knowledge base.";
                    $response["category"] = $localCategory;
                    $response["suggestions"] = getStaticSuggestions($localCategory);
                    array_pop($_SESSION['buddy_history']);
                }
            }
        }
    } else {
        $response["answer"] = "Tell me something, buddy!";
        $response["suggestions"] = ["Where is library?", "Show bus timing"];
    }
} else {
    $response["answer"] = "Invalid request method.";
    $response["suggestions"] = [];
}

echo json_encode($response);
exit;
?>
