<?php
/**
 * Admin Panel - Buddy AI Chatbot Analytics View
 */
require_once __DIR__ . '/includes/header.php';

// Clear analytics logs if requested
if (isset($_POST['action']) && $_POST['action'] === 'clear_logs') {
    $db->exec("DELETE FROM buddy_analytics");
    $db->exec("DELETE FROM gemini_cache");
    $_SESSION['toast'] = ["type" => "success", "message" => "All chatbot query logs and response cache cleared successfully!"];
    header("Location: buddy_analytics.php");
    exit;
}

// Fetch general stats
$total_queries = $db->query("SELECT COUNT(*) FROM buddy_analytics")->fetchColumn();

$kb_hits = $db->query("SELECT COUNT(*) FROM buddy_analytics WHERE answered_by = 'Knowledge Base'")->fetchColumn();
$gemini_hits = $db->query("SELECT COUNT(*) FROM buddy_analytics WHERE answered_by = 'Gemini'")->fetchColumn();

$kb_pct = $total_queries > 0 ? round(($kb_hits / $total_queries) * 100, 1) : 0;
$gemini_pct = $total_queries > 0 ? round(($gemini_hits / $total_queries) * 100, 1) : 0;

$avg_latency = $db->query("SELECT AVG(response_time) FROM buddy_analytics")->fetchColumn() ?: 0;
$avg_latency = round($avg_latency, 3);

$cache_count = $db->query("SELECT COUNT(*) FROM gemini_cache")->fetchColumn();

// Fetch last 100 queries
$recent_queries_stmt = $db->query("SELECT * FROM buddy_analytics ORDER BY created_at DESC LIMIT 100");
$recent_queries = $recent_queries_stmt->fetchAll();
?>

<!-- Header Section -->
<div class="top-navbar glass-panel">
    <div class="nav-title">
        <h1>Buddy AI Chatbot Analytics</h1>
        <p>Monitor conversation intents, fallbacks, system performance, and cache rates.</p>
    </div>
    
    <form method="POST" onsubmit="return confirm('Are you sure you want to delete all chatbot analytics logs and cached responses? This cannot be undone.');">
        <input type="hidden" name="action" value="clear_logs">
        <button type="submit" class="btn-glass btn-danger" style="background: rgba(239, 68, 68, 0.15); border-color: rgba(239, 68, 68, 0.3); color: #f87171;">
            <i class="fa-solid fa-trash-arrow-up"></i> RESET ANALYTICS LOGS
        </button>
    </form>
</div>

<!-- Grid Cards for Statistics -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px;">
    <!-- Card 1: Total Queries -->
    <div class="glass-panel" style="padding: 24px; text-align: center; border-radius: 16px;">
        <i class="fa-solid fa-comments" style="font-size: 2.2rem; color: var(--glow-primary); margin-bottom: 12px; display: inline-block;"></i>
        <h4 style="font-size: 0.82rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em;">Total Student Queries</h4>
        <p style="font-size: 2.4rem; font-weight: 800; color: var(--text-primary); margin-top: 8px;"><?php echo $total_queries; ?></p>
    </div>

    <!-- Card 2: Knowledge Base Hits -->
    <div class="glass-panel" style="padding: 24px; text-align: center; border-radius: 16px;">
        <i class="fa-solid fa-database" style="font-size: 2.2rem; color: #10b981; margin-bottom: 12px; display: inline-block;"></i>
        <h4 style="font-size: 0.82rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em;">Knowledge Base Matches</h4>
        <p style="font-size: 2.4rem; font-weight: 800; color: #10b981; margin-top: 8px;">
            <?php echo $kb_hits; ?> <span style="font-size: 1rem; color: var(--text-secondary); font-weight: 500;">(<?php echo $kb_pct; ?>%)</span>
        </p>
    </div>

    <!-- Card 3: Gemini Fallbacks -->
    <div class="glass-panel" style="padding: 24px; text-align: center; border-radius: 16px;">
        <i class="fa-solid fa-brain" style="font-size: 2.2rem; color: #a78bfa; margin-bottom: 12px; display: inline-block;"></i>
        <h4 style="font-size: 0.82rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em;">Gemini AI Fallbacks</h4>
        <p style="font-size: 2.4rem; font-weight: 800; color: #a78bfa; margin-top: 8px;">
            <?php echo $gemini_hits; ?> <span style="font-size: 1rem; color: var(--text-secondary); font-weight: 500;">(<?php echo $gemini_pct; ?>%)</span>
        </p>
    </div>

    <!-- Card 4: Average Latency -->
    <div class="glass-panel" style="padding: 24px; text-align: center; border-radius: 16px;">
        <i class="fa-solid fa-gauge-high" style="font-size: 2.2rem; color: #fbbf24; margin-bottom: 12px; display: inline-block;"></i>
        <h4 style="font-size: 0.82rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em;">Average Latency</h4>
        <p style="font-size: 2.4rem; font-weight: 800; color: #fbbf24; margin-top: 8px;">
            <?php echo $avg_latency; ?> <span style="font-size: 1.1rem; font-weight: 500; color: var(--text-secondary);">sec</span>
        </p>
    </div>

    <!-- Card 5: Caching Count -->
    <div class="glass-panel" style="padding: 24px; text-align: center; border-radius: 16px;">
        <i class="fa-solid fa-bolt" style="font-size: 2.2rem; color: #38bdf8; margin-bottom: 12px; display: inline-block;"></i>
        <h4 style="font-size: 0.82rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em;">Cached Responses</h4>
        <p style="font-size: 2.4rem; font-weight: 800; color: #38bdf8; margin-top: 8px;"><?php echo $cache_count; ?></p>
    </div>
</div>

<!-- Logs Table -->
<div class="glass-panel" style="padding: 28px; border-radius: 16px;">
    <h3 style="font-size: 1.2rem; color: var(--text-primary); margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
        <i class="fa-solid fa-list-check" style="color: var(--glow-primary);"></i> Chatbot Interaction Logs (Last 100 Entries)
    </h3>
    
    <div class="table-responsive" style="overflow-x: auto;">
        <table class="table" style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border-light); color: var(--text-secondary); font-size: 0.85rem; text-transform: uppercase;">
                    <th style="padding: 14px 10px;">ID</th>
                    <th style="padding: 14px 10px; width: 40%;">Student Question</th>
                    <th style="padding: 14px 10px;">Category</th>
                    <th style="padding: 14px 10px;">Answered By</th>
                    <th style="padding: 14px 10px;">Latency</th>
                    <th style="padding: 14px 10px;">Date & Time</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recent_queries)): ?>
                    <tr>
                        <td colspan="6" style="padding: 30px; text-align: center; color: var(--text-tertiary); font-style: italic;">
                            No query logs recorded yet. Start conversing with Buddy to populate logs!
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recent_queries as $q): ?>
                        <tr style="border-bottom: 1px solid var(--border-light); color: var(--text-primary); font-size: 0.9rem;">
                            <td style="padding: 14px 10px; color: var(--text-tertiary);">#<?php echo $q['id']; ?></td>
                            <td style="padding: 14px 10px; font-weight: 500;"><?php echo sanitize_input($q['question']); ?></td>
                            <td style="padding: 14px 10px;">
                                <span style="font-size: 0.78rem; padding: 4px 8px; border-radius: 4px; background: rgba(255,255,255,0.03); border: 1px solid var(--border-light);">
                                    <?php echo sanitize_input($q['category']); ?>
                                </span>
                            </td>
                            <td style="padding: 14px 10px;">
                                <?php if ($q['answered_by'] === 'Knowledge Base'): ?>
                                    <span style="color: #10b981; font-weight: 600;"><i class="fa-solid fa-circle-check"></i> KB Match</span>
                                <?php else: ?>
                                    <span style="color: #a78bfa; font-weight: 600;"><i class="fa-solid fa-wand-magic-sparkles"></i> Gemini AI</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 14px 10px; font-weight: bold; color: <?php echo $q['response_time'] > 1.0 ? '#f87171' : '#34d399'; ?>;">
                                <?php echo round($q['response_time'], 3); ?>s
                            </td>
                            <td style="padding: 14px 10px; color: var(--text-secondary); font-size: 0.8rem;"><?php echo $q['created_at']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
