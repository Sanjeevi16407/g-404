    </main>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });

        // Modal helper functions for global CRUD admin actions
        function openModal(id) {
            document.getElementById(id).classList.add('active');
        }
        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
        }

        // Global live upload image preview handler
        document.addEventListener('change', function(e) {
            if (e.target && e.target.type === 'file' && e.target.accept.includes('image')) {
                const fileInput = e.target;
                const file = fileInput.files[0];
                
                if (file) {
                    // Look for a preview container in the parent element
                    const parentForm = fileInput.closest('form');
                    let previewContainer = parentForm.querySelector('.upload-preview-container');
                    
                    // Create preview container dynamically if not present
                    if (!previewContainer) {
                        previewContainer = document.createElement('div');
                        previewContainer.className = 'upload-preview-container';
                        previewContainer.style.marginTop = '10px';
                        previewContainer.style.marginBottom = '16px';
                        previewContainer.style.textAlign = 'center';
                        
                        const img = document.createElement('img');
                        img.className = 'upload-preview-img';
                        img.style.maxWidth = '120px';
                        img.style.maxHeight = '120px';
                        img.style.borderRadius = '12px';
                        img.style.objectFit = 'cover';
                        img.style.border = '2px solid var(--glow-primary)';
                        img.style.boxShadow = '0 0 15px var(--glow-primary-alpha)';
                        
                        previewContainer.appendChild(img);
                        fileInput.parentNode.insertBefore(previewContainer, fileInput.nextSibling);
                    }
                    
                    const previewImg = previewContainer.querySelector('img');
                    const reader = new FileReader();
                    
                    reader.onload = function(event) {
                        previewImg.src = event.target.result;
                        previewContainer.style.display = 'block';
                    };
                    
                    reader.readAsDataURL(file);
                }
            }
        });
    </script>
</body>
</html>
