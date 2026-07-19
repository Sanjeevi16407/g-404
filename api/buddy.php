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
    echo json_encode(["status" => "cleared", "answer" => "Conversation history reset successfully."]);
    exit;
}

$response = ["answer" => ""];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $query = trim($_POST['query'] ?? '');
    
    if (!empty($query)) {
        // Log query event to analytics
        try {
            $log_stmt = $db->prepare("INSERT INTO analytics_logs (event_type, item_name) VALUES ('buddy_query', ?)");
            $log_stmt->execute([substr($query, 0, 100)]);
        } catch (PDOException $e) {}

        // 1. Scan Local FAQ Knowledge Base (buddy_knowledge table)
        $found_local_match = false;
        $faq_stmt = $db->query("SELECT * FROM buddy_knowledge WHERE status = 'active'");
        $faqs = $faq_stmt->fetchAll();

        foreach ($faqs as $faq) {
            $keywords = array_map('trim', explode(',', $faq['question_keywords']));
            foreach ($keywords as $kw) {
                if (!empty($kw) && stripos($query, $kw) !== false) {
                    $response["answer"] = $faq['answer'];
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
                    break 2;
                }
            }
        }

        // 2. Fallback: Google Gemini API with Conversational Memory & RAG Context
        if (!$found_local_match) {
            // Append current user message to session memory
            $_SESSION['buddy_history'][] = [
                "role" => "user",
                "parts" => [["text" => $query]]
            ];

            // Keep conversation history capped at last 10 messages (5 turns)
            if (count($_SESSION['buddy_history']) > 10) {
                $_SESSION['buddy_history'] = array_slice($_SESSION['buddy_history'], -10);
            }

            // Real-Time Database Context Scraper (RAG)
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
            
            // Timetable Queries
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
            
            // Faculty / Teacher Queries
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
            
            // Campus Locations & Cafeteria / Library timings
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
            
            // Combine RAG Context
            $rag_context = $student_context . $timetable_context . $faculty_context . $campus_context . $club_context . $event_context . $announcement_context;

            // Fetch settings
            $buddy_settings = $db->query("SELECT gemini_api_key, buddy_name FROM buddy_settings WHERE id = 1 LIMIT 1")->fetch();
            $api_key = $buddy_settings['gemini_api_key'] ?? GEMINI_API_KEY;
            $buddy_name = $buddy_settings['buddy_name'] ?? 'Buddy';

            if (!empty($api_key)) {
                $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $api_key;
                
                $system_instruction = "You are $buddy_name, a warm, friendly and helpful Digital Senior (mentor) guiding first-year engineering freshers at Saranathan College of Engineering. " .
                                     "You are extremely supportive, witty, and knowledgeable. You speak English, Tamil, and Tanglish (e.g. 'library Block A la ground floor la iruku buddy'). " .
                                     "If the student queries you in Tamil or Tanglish, reply in a warm Tamil/Tanglish mix to make them feel comfortable! " .
                                     "You have memory of previous messages. Maintain continuity. Keep your answers brief, clear, and structured. Do not use placeholders or markdown formatting.";

                if (!empty($rag_context)) {
                    $system_instruction .= "\n\nUse the following real-time database context from the portal to answer the student's question accurately:\n" . $rag_context;
                }

                $payload = [
                    "contents" => array_values($_SESSION['buddy_history']), // reset keys for JSON array
                    "systemInstruction" => [
                        "parts" => [
                            ["text" => $system_instruction]
                        ]
                    ],
                    "generationConfig" => [
                        "maxOutputTokens" => 350,
                        "temperature" => 0.7
                    ]
                ];

                // Send request via cURL
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

                $curl_response = curl_exec($ch);
                
                if (curl_errno($ch)) {
                    $response["answer"] = "I had trouble matching that query locally and couldn't establish a secure bridge to the Gemini network. Try asking: 'Where is library?'";
                    array_pop($_SESSION['buddy_history']);
                } else {
                    $result = json_decode($curl_response, true);
                    $answer = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
                    
                    if (!empty($answer)) {
                        $response["answer"] = trim($answer);
                        // Save model turn to history
                        $_SESSION['buddy_history'][] = [
                            "role" => "model",
                            "parts" => [["text" => trim($answer)]]
                        ];
                    } else {
                        $response["answer"] = "I received a blank response from the Gemini brain model. Please rephrase your question!";
                        array_pop($_SESSION['buddy_history']);
                    }
                }
                curl_close($ch);
            } else {
                $response["answer"] = "My online Gemini API key is currently inactive. Ask me local questions like 'Where is library?' or 'Canteen timings'.";
                array_pop($_SESSION['buddy_history']);
            }
        }
    } else {
        $response["answer"] = "Tell me something, buddy!";
    }
} else {
    $response["answer"] = "Invalid request method.";
}

echo json_encode($response);
exit;
?>
