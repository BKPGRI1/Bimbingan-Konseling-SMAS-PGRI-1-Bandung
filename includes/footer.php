<?php
// includes/footer.php
?>
            </div> <!-- End content-wrapper -->
            
            <footer class="footer">
                <div class="container-fluid">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <span>&copy; <?= date('Y') ?> <?= APP_NAME ?>. </span>
                            <span>By: <a href="https://shopee.co.id/viralytics_shop" target="_blank" class="text-decoration-none" style="color: #6C63FF; font-weight: 600;">Viralytics_Shop</a></span>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <span class="text-muted">Version <?= defined('APP_VERSION') ? APP_VERSION : '1.0.0' ?></span>
                        </div>
                    </div>
                </div>
            </footer>
        </div> <!-- End main-content -->
    </div> <!-- End app-wrapper -->
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>