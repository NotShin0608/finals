<footer class="footer mt-auto py-3 bg-light">
    <div class="container text-center">
        <span class="text-muted">© <?php echo date('Y'); ?> Financial Management System</span>
    </div>
</footer>

<!-- Add floating action button for help -->
<div class="position-fixed bottom-0 end-0 p-3">
    <button type="button" class="btn btn-primary btn-lg rounded-circle" data-bs-toggle="modal" data-bs-target="#helpModal">
        <i class="bi bi-question-lg"></i> ?
    </button>
</div>

<!-- Help Modal -->
<div class="modal fade" id="helpModal" tabindex="-1" aria-labelledby="helpModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="helpModalLabel">Help & Support</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6>Need assistance?</h6>
                <p>If you need help using the Financial Management System, please refer to the following resources:</p>
                <ul>
                    <li><a href="user-manual.php" target="_blank">User Manual</a></li>
                    <li><a href="faq.php" target="_blank">Frequently Asked Questions</a></li>
                    <li>Contact support at: support@example.com</li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
