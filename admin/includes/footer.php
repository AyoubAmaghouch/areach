
</div><!-- /#admin-content -->
</div><!-- /#admin-main -->
</div><!-- /#admin-wrapper -->

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Initialize Bootstrap tooltips -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Bootstrap tooltips
    const tooltipEls = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipEls.forEach(function (el) {
        new bootstrap.Tooltip(el, { trigger: 'hover' });
    });

    // Auto-dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.flash-alert[data-auto-dismiss]');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity .4s';
            setTimeout(function () { alert.remove(); }, 400);
        }, 5000);
    });
});
</script>

</body>
</html>