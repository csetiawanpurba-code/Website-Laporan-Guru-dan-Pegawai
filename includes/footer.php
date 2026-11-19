    </main>

    <!-- Overlay for mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- JavaScript -->
    <script src="<?php echo BASE_URL; ?>/assets/js/app.js"></script>
    
    <?php if (isset($_SESSION['toast'])): ?>
    <script>
        showToast('<?php echo $_SESSION['toast']['message']; ?>', '<?php echo $_SESSION['toast']['type']; ?>');
    </script>
    <?php unset($_SESSION['toast']); ?>
    <?php endif; ?>
    
    <style>
        /* Additional Badge Colors */
        .badge-kepala-sekolah { background: #fef3c7; color: #b45309; }
        .badge-disetujui { background: #d1fae5; color: #047857; }
        .badge-ditolak { background: #fee2e2; color: #b91c1c; }
        .badge-pending { background: #fef3c7; color: #b45309; }
        
        /* Stats Card Colors */
        .stat-card.pending .stat-value { color: #f59e0b; }
        .stat-card.approved .stat-value { color: #10b981; }
        .stat-card.rejected .stat-value { color: #ef4444; }
        .stat-card.total .stat-value { color: #6366f1; }
        
        /* User Avatar in Table */
        .user-avatar {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #6366f1, #818cf8);
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.875rem;
        }
        
        /* Table Actions */
        .table-actions {
            display: flex;
            gap: 0.25rem;
            justify-content: flex-end;
        }
        
        .table-actions .btn {
            padding: 0.5rem;
            min-width: 32px;
            height: 32px;
        }
    </style>
</body>
</html>
