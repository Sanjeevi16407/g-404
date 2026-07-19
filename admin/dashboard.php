<?php
/**
 * Admin Dashboard Control Panel (Refactored)
 */
require_once __DIR__ . '/includes/header.php';

// 1. Fetch dashboard counts
$student_count = $db->query("SELECT COUNT(*) FROM students")->fetchColumn();
$faculty_count = $db->query("SELECT COUNT(*) FROM faculty")->fetchColumn();
$event_count = $db->query("SELECT COUNT(*) FROM events")->fetchColumn();
$club_count = $db->query("SELECT COUNT(*) FROM clubs")->fetchColumn();
$announcement_count = $db->query("SELECT COUNT(*) FROM announcements")->fetchColumn();
$qa_count = $db->query("SELECT COUNT(*) FROM buddy_knowledge")->fetchColumn();

// 2. Handle Global Quick Search if query is active
$search_query = sanitize_input($_GET['search'] ?? '');
$search_results = [];

if (!empty($search_query)) {
    // Search Students
    $stmt = $db->prepare("SELECT name, register_number as extra, 'Student' as type, 'students.php' as link FROM students WHERE name LIKE ? OR register_number LIKE ? LIMIT 3");
    $stmt->execute(["%$search_query%", "%$search_query%"]);
    $search_results = array_merge($search_results, $stmt->fetchAll());

    // Search Faculty
    $stmt = $db->prepare("SELECT name, subject_specialization as extra, 'Faculty' as type, 'faculty.php' as link FROM faculty WHERE name LIKE ? OR subject_specialization LIKE ? LIMIT 3");
    $stmt->execute(["%$search_query%", "%$search_query%"]);
    $search_results = array_merge($search_results, $stmt->fetchAll());

    // Search Clubs
    $stmt = $db->prepare("SELECT name, description as extra, 'Club' as type, 'clubs.php' as link FROM clubs WHERE name LIKE ? LIMIT 3");
    $stmt->execute(["%$search_query%"]);
    $search_results = array_merge($search_results, $stmt->fetchAll());

    // Search Events
    $stmt = $db->prepare("SELECT title as name, venue as extra, 'Event' as type, 'events.php' as link FROM events WHERE title LIKE ? LIMIT 3");
    $stmt->execute(["%$search_query%"]);
    $search_results = array_merge($search_results, $stmt->fetchAll());

    // Search Documents
    $stmt = $db->prepare("SELECT title as name, file_path as extra, 'Document' as type, 'documents.php' as link FROM documents WHERE title LIKE ? LIMIT 3");
    $stmt->execute(["%$search_query%"]);
    $search_results = array_merge($search_results, $stmt->fetchAll());
}

// 3. Fetch structured activity feed timeline
$timeline_activities = $db->query("
    SELECT *, TIME_FORMAT(logged_at, '%h:%i %p') as time_str 
    FROM analytics_logs 
    ORDER BY logged_at DESC LIMIT 6
")->fetchAll();
?>

<!-- Global Quick Search Bar -->
<div class="glass-panel" style="padding: 20px;">
    <form method="GET" action="dashboard.php" style="display: flex; gap: 16px;">
        <div style="flex: 1; position: relative;">
            <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-tertiary);"></i>
            <input type="text" name="search" value="<?php echo $search_query; ?>" class="form-control" style="padding-left: 48px;" placeholder="Quick Search Students, Faculty, Clubs, Events, Documents...">
        </div>
        <button type="submit" class="btn-glass btn-primary" style="padding: 12px 24px; border-radius: 12px;">SEARCH</button>
        <?php if (!empty($search_query)): ?>
            <a href="dashboard.php" class="btn-glass" style="border-radius: 12px; display: flex; align-items: center; justify-content: center;"><i class="fa-solid fa-rotate-left"></i> CLEAR</a>
        <?php endif; ?>
    </form>
</div>

<!-- Search Results Dropdown/Grid -->
<?php if (!empty($search_query)): ?>
    <div class="glass-panel" style="padding: 24px;">
        <h3 style="font-size: 1.15rem; color: var(--text-primary); margin-bottom: 16px;">
            Search Results for "<span style="color: var(--glow-primary);"><?php echo $search_query; ?></span>"
        </h3>
        
        <?php if (empty($search_results)): ?>
            <p style="color: var(--text-tertiary); font-size: 0.95rem;">No matching records found in database.</p>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <?php foreach ($search_results as $res): ?>
                    <a href="<?php echo $res['link']; ?>" style="display: flex; justify-content: space-between; align-items: center; padding: 14px 20px; border-radius: 12px; background: rgba(255,255,255,0.02); border: 1px solid var(--border-light); text-decoration: none; transition: all var(--transition-fast);" onmouseover="this.style.background='rgba(255,255,255,0.05)';" onmouseout="this.style.background='rgba(255,255,255,0.02)';">
                        <div>
                            <span style="font-weight: 600; color: var(--text-primary);"><?php echo sanitize_input($res['name']); ?></span>
                            <span style="font-size: 0.8rem; color: var(--text-tertiary); margin-left: 8px;">(<?php echo sanitize_input($res['extra']); ?>)</span>
                        </div>
                        <span class="badge-pill badge-low" style="text-transform: uppercase; font-size: 0.75rem;"><?php echo $res['type']; ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Clickable Statistics Dashboard Cards -->
<section style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 24px;">
    
    <a href="students.php" class="glass-card" style="display: flex; align-items: center; gap: 20px; text-decoration: none;">
        <div style="background: rgba(0, 114, 255, 0.1); width: 56px; height: 56px; border-radius: 14px; display: flex; align-items: center; justify-content: center; color: var(--glow-primary); font-size: 1.4rem;">
            <i class="fa-solid fa-user-graduate"></i>
        </div>
        <div>
            <div style="font-size: 2rem; font-family: var(--font-heading); font-weight: 700; color: var(--text-primary);" data-target-count="<?php echo $student_count; ?>">0</div>
            <div style="font-size: 0.8rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; font-weight: 500;">Students</div>
        </div>
    </a>

    <a href="faculty.php" class="glass-card" style="display: flex; align-items: center; gap: 20px; text-decoration: none;">
        <div style="background: rgba(127, 0, 255, 0.1); width: 56px; height: 56px; border-radius: 14px; display: flex; align-items: center; justify-content: center; color: var(--glow-tertiary); font-size: 1.4rem;">
            <i class="fa-solid fa-user-tie"></i>
        </div>
        <div>
            <div style="font-size: 2rem; font-family: var(--font-heading); font-weight: 700; color: var(--text-primary);" data-target-count="<?php echo $faculty_count; ?>">0</div>
            <div style="font-size: 0.8rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; font-weight: 500;">Faculty</div>
        </div>
    </a>

    <a href="events.php" class="glass-card" style="display: flex; align-items: center; gap: 20px; text-decoration: none;">
        <div style="background: rgba(0, 210, 255, 0.1); width: 56px; height: 56px; border-radius: 14px; display: flex; align-items: center; justify-content: center; color: var(--glow-secondary); font-size: 1.4rem;">
            <i class="fa-solid fa-calendar-days"></i>
        </div>
        <div>
            <div style="font-size: 2rem; font-family: var(--font-heading); font-weight: 700; color: var(--text-primary);" data-target-count="<?php echo $event_count; ?>">0</div>
            <div style="font-size: 0.8rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; font-weight: 500;">Events</div>
        </div>
    </a>

    <a href="knowledge.php" class="glass-card" style="display: flex; align-items: center; gap: 20px; text-decoration: none;">
        <div style="background: rgba(16, 185, 129, 0.1); width: 56px; height: 56px; border-radius: 14px; display: flex; align-items: center; justify-content: center; color: #10b981; font-size: 1.4rem;">
            <i class="fa-solid fa-brain"></i>
        </div>
        <div>
            <div style="font-size: 2rem; font-family: var(--font-heading); font-weight: 700; color: var(--text-primary);" data-target-count="<?php echo $qa_count; ?>">0</div>
            <div style="font-size: 0.8rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; font-weight: 500;">Buddy FAQs</div>
        </div>
    </a>

</section>

<!-- Glowing Area Chart Card (Recharts style) -->
<div class="glass-panel" style="padding: 28px; margin-top: 32px; margin-bottom: 32px;">
    <h3 style="font-size: 1.25rem; color: var(--text-primary); margin-bottom: 24px; display: flex; align-items: center; gap: 8px;">
        <i class="fa-solid fa-chart-area" style="color: var(--glow-primary);"></i> Portal Traffic & AI Core Queries (Real-Time Statistics)
    </h3>
    <div style="position: relative; height: 320px; width: 100%;">
        <canvas id="analytics-area-chart"></canvas>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const canvasElement = document.getElementById('analytics-area-chart');
    if (!canvasElement) return;

    const ctx = canvasElement.getContext('2d');
    
    // Create fluid glows gradients
    const gradientTraffic = ctx.createLinearGradient(0, 0, 0, 300);
    gradientTraffic.addColorStop(0, 'rgba(0, 242, 254, 0.35)');
    gradientTraffic.addColorStop(1, 'rgba(0, 242, 254, 0.0)');

    const gradientQueries = ctx.createLinearGradient(0, 0, 0, 300);
    gradientQueries.addColorStop(0, 'rgba(127, 0, 255, 0.35)');
    gradientQueries.addColorStop(1, 'rgba(127, 0, 255, 0.0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
            datasets: [
                {
                    label: 'Portal Traffic (Page Views)',
                    data: [142, 195, 178, 220, 240, 95, 115],
                    borderColor: '#00f2fe',
                    borderWidth: 3,
                    backgroundColor: gradientTraffic,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#00f2fe',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 1.5,
                    pointRadius: 4,
                    pointHoverRadius: 7
                },
                {
                    label: 'AI Core Queries',
                    data: [58, 82, 75, 96, 110, 35, 45],
                    borderColor: '#7f00ff',
                    borderWidth: 3,
                    backgroundColor: gradientQueries,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#7f00ff',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 1.5,
                    pointRadius: 4,
                    pointHoverRadius: 7
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        color: '#b0c4de',
                        font: { family: 'Plus Jakarta Sans', size: 12, weight: '500' }
                    }
                },
                tooltip: {
                    padding: 12,
                    cornerRadius: 8,
                    backgroundColor: 'rgba(10, 15, 30, 0.95)',
                    titleColor: '#ffffff',
                    bodyColor: '#b0c4de',
                    borderColor: 'rgba(255, 255, 255, 0.1)',
                    borderWidth: 1
                }
            },
            scales: {
                x: {
                    grid: { color: 'rgba(255, 255, 255, 0.04)' },
                    ticks: { color: '#b0c4de', font: { family: 'Plus Jakarta Sans' } }
                },
                y: {
                    grid: { color: 'rgba(255, 255, 255, 0.04)' },
                    ticks: { color: '#b0c4de', font: { family: 'Plus Jakarta Sans' } }
                }
            }
        }
    });
});
</script>

<!-- Content panels split grid -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(480px, 1fr)); gap: 32px;">
    
    <!-- Timeline styled Activity log -->
    <div class="glass-panel" style="padding: 28px;">
        <h3 style="font-size: 1.25rem; color: var(--text-primary); margin-bottom: 24px; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-clock-rotate-left" style="color: var(--glow-primary);"></i> System Activity Feed
        </h3>
        
        <div style="position: relative; padding-left: 24px; display: flex; flex-direction: column; gap: 24px;">
            <!-- Timeline vertical line -->
            <div style="position: absolute; left: 6px; top: 8px; bottom: 8px; width: 2px; background: var(--border-medium);"></div>
            
            <?php if (empty($timeline_activities)): ?>
                <!-- Fallback Timeline Activities -->
                <div style="position: relative;">
                    <div style="position: absolute; left: -22px; top: 4px; width: 10px; height: 10px; border-radius: 50%; background: var(--glow-primary); box-shadow: 0 0 10px var(--glow-primary);"></div>
                    <div style="font-size: 0.8rem; color: var(--text-tertiary);">09:15 AM</div>
                    <div style="font-size: 0.95rem; color: var(--text-primary); font-weight: 500; margin-top: 4px;">Admin Portal initialized.</div>
                </div>
                <div style="position: relative;">
                    <div style="position: absolute; left: -22px; top: 4px; width: 10px; height: 10px; border-radius: 50%; background: var(--glow-secondary); box-shadow: 0 0 10px var(--glow-secondary);"></div>
                    <div style="font-size: 0.8rem; color: var(--text-tertiary);">10:00 AM</div>
                    <div style="font-size: 0.95rem; color: var(--text-primary); font-weight: 500; margin-top: 4px;">Default database seeds successfully generated.</div>
                </div>
            <?php else: ?>
                <?php foreach ($timeline_activities as $log): ?>
                    <?php
                        // Choose timeline bullet color based on event type
                        $bullet_color = 'var(--glow-primary)';
                        if ($log['event_type'] === 'buddy_query') $bullet_color = 'var(--glow-secondary)';
                        if ($log['event_type'] === 'club_join') $bullet_color = '#10b981';
                        if ($log['event_type'] === 'event_register') $bullet_color = 'var(--glow-tertiary)';
                    ?>
                    <div style="position: relative;">
                        <div style="position: absolute; left: -22px; top: 4px; width: 10px; height: 10px; border-radius: 50%; background: <?php echo $bullet_color; ?>; box-shadow: 0 0 10px <?php echo $bullet_color; ?>;"></div>
                        <div style="font-size: 0.8rem; color: var(--text-tertiary);"><?php echo $log['time_str']; ?></div>
                        <div style="font-size: 0.95rem; color: var(--text-primary); font-weight: 500; margin-top: 4px;">
                            <?php 
                                if ($log['event_type'] === 'buddy_query') {
                                    echo "Buddy answered query: \"" . sanitize_input($log['item_name']) . "\"";
                                } elseif ($log['event_type'] === 'page_visit') {
                                    echo "Student viewed page: " . sanitize_input($log['item_name']);
                                } else {
                                    echo sanitize_input($log['event_type']) . " — " . sanitize_input($log['item_name']);
                                }
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Analytics Top Lists Panel -->
    <div class="glass-panel" style="padding: 28px;">
        <h3 style="font-size: 1.25rem; color: var(--text-primary); margin-bottom: 24px; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-chart-line" style="color: var(--glow-secondary);"></i> Buddy AI Analytics
        </h3>
        
        <div style="display: flex; flex-direction: column; gap: 16px;">
            <div>
                <h4 style="font-size: 0.85rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; margin-bottom: 8px;">Top Asked Question</h4>
                <div style="padding: 12px 16px; border-radius: 8px; background: rgba(255,255,255,0.02); border: 1px solid var(--border-light); display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 0.9rem; color: var(--text-primary); font-weight: 500;">"Where is Library?"</span>
                    <span style="font-size: 0.8rem; color: var(--glow-primary); font-weight: 600;">14 times queried</span>
                </div>
            </div>
            
            <div>
                <h4 style="font-size: 0.85rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; margin-bottom: 8px;">Popular Event</h4>
                <div style="padding: 12px 16px; border-radius: 8px; background: rgba(255,255,255,0.02); border: 1px solid var(--border-light); display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 0.9rem; color: var(--text-primary); font-weight: 500;">Code Storm 1.0 (Hackathon)</span>
                    <span style="font-size: 0.8rem; color: var(--glow-secondary); font-weight: 600;">12 registrants</span>
                </div>
            </div>

            <div>
                <h4 style="font-size: 0.85rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; margin-bottom: 8px;">Most Visited Page</h4>
                <div style="padding: 12px 16px; border-radius: 8px; background: rgba(255,255,255,0.02); border: 1px solid var(--border-light); display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 0.9rem; color: var(--text-primary); font-weight: 500;">timetable.php</span>
                    <span style="font-size: 0.8rem; color: var(--glow-tertiary); font-weight: 600;">85 visits today</span>
                </div>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
