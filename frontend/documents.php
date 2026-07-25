<?php
/**
 * Student Portal - Academic Documents Download Panel
 */
require_once __DIR__ . '/includes/header.php';

// Fetch all academic documents
$documents = $db->query("
    SELECT * 
    FROM documents 
    ORDER BY uploaded_at DESC
")->fetchAll();
?>

<div class="page-header">
    <div class="page-title">📄 Academic Documents & Circulars</div>
</div>

<!-- Search bar -->
<div class="glass-panel" style="padding: 20px; margin-top: 16px; display: grid; grid-template-columns: 2fr 1fr; gap: 16px; align-items: flex-end;">
    <div class="form-group" style="margin-bottom: 0;">
        <label class="form-label" style="font-size: 0.75rem;">Search Documents</label>
        <input type="text" id="doc-search-input" oninput="filterDocuments()" class="form-control" placeholder="Search by document title or filename...">
    </div>
    
    <button onclick="filterDocuments()" class="btn-glass btn-primary" style="padding: 12px; border-radius: 12px; height: 46px; display: flex; align-items: center; justify-content: center; gap: 8px;">
        <i class="fa-solid fa-magnifying-glass"></i> Search
    </button>
</div>

<div style="margin-top: 24px;">
    
    <!-- No results fallback -->
    <div id="no-results-panel" class="glass-panel" style="padding: 40px; text-align: center; color: var(--text-tertiary); display: none;">
        No documents match your search query.
    </div>

    <?php if (empty($documents)): ?>
        <div class="glass-panel" style="padding: 40px; text-align: center; color: var(--text-tertiary);">
            📭 No academic documents have been uploaded by the administration yet.
        </div>
    <?php else: ?>
        <div id="documents-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px;">
            <?php foreach ($documents as $doc): ?>
                <div class="glass-card document-card" data-title="<?php echo sanitize_input($doc['title']); ?>" style="display: flex; flex-direction: column; justify-content: space-between; padding: 24px; position: relative; overflow: hidden; min-height: 200px;">
                    <!-- Top border colored glow -->
                    <div style="position: absolute; top: 0; left: 0; width: 100%; height: 3px; background: linear-gradient(90deg, var(--glow-primary), var(--glow-secondary));"></div>
                    
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
                            <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(0, 242, 254, 0.08); display: flex; align-items: center; justify-content: center; color: var(--glow-primary); font-size: 1.5rem;">
                                <i class="fa-solid fa-file-pdf"></i>
                            </div>
                            <span style="font-size: 0.7rem; color: var(--text-tertiary);"><?php echo date('M d, Y', strtotime($doc['uploaded_at'])); ?></span>
                        </div>
                        
                        <h4 style="font-size: 1.05rem; color: var(--text-primary); font-weight: 700; line-height: 1.4; margin-bottom: 8px;" class="doc-title-text"><?php echo sanitize_input($doc['title']); ?></h4>
                    </div>

                    <div style="margin-top: 20px; border-top: 1px solid var(--border-light); padding-top: 16px; display: flex; justify-content: space-between; align-items: center; gap: 8px;">
                        <a href="../<?php echo $doc['file_path']; ?>" target="_blank" class="btn-glass btn-secondary" style="display: inline-flex; align-items: center; gap: 6px; text-decoration: none; padding: 8px 14px; border-radius: 8px; font-size: 0.75rem; font-weight: 600; border: 1px solid rgba(255, 255, 255, 0.1);">
                            <i class="fa-solid fa-eye"></i> View PDF
                        </a>
                        <a href="../<?php echo $doc['file_path']; ?>" download="<?php echo sanitize_input($doc['title']); ?>.pdf" class="btn-glass btn-primary" style="display: inline-flex; align-items: center; gap: 6px; text-decoration: none; padding: 8px 14px; border-radius: 8px; font-size: 0.75rem; font-weight: 600;">
                            <i class="fa-solid fa-download"></i> Download
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<script>
function filterDocuments() {
    const query = document.getElementById('doc-search-input').value.toLowerCase().trim();
    const cards = document.querySelectorAll('.document-card');
    let visibleCount = 0;
    
    cards.forEach(card => {
        const title = card.getAttribute('data-title').toLowerCase();
        
        if (title.includes(query)) {
            card.style.display = 'flex';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });
    
    const noResults = document.getElementById('no-results-panel');
    if (visibleCount === 0 && cards.length > 0) {
        noResults.style.display = 'block';
    } else {
        noResults.style.display = 'none';
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
