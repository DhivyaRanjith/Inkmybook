</div> <!-- End main-content -->

<footer class="pt-5 pb-4">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="d-flex align-items-center mb-3">
                    <img src="/inkmybook/assets/img/logo.png" alt="InkMyBook" height="30" class="me-2 grayscale"
                        style="filter: brightness(0) invert(1);">
                    <h5 class="mb-0 fw-bold text-white">InkMyBook</h5>
                </div>
                <p class="text-muted small mb-4">InkMyBook is the world's largest freelancing and crowdsourcing
                    marketplace by number of users and projects. We connect over 60 million employers and freelancers
                    globally from over 247 countries, regions and territories.</p>
                <div class="d-flex gap-3">
                    <a href="#" class="text-white"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="text-white"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-white"><i class="fab fa-youtube"></i></a>
                    <a href="#" class="text-white"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
            <div class="col-lg-2 col-md-6 mb-4">
                <h6 class="fw-bold mb-3 text-white">Network</h6>
                <ul class="list-unstyled small">
                    <li class="mb-2"><a href="#">Browse Categories</a></li>
                    <li class="mb-2"><a href="#">Browse Projects</a></li>
                    <li class="mb-2"><a href="#">Browse Freelancers</a></li>
                    <li class="mb-2"><a href="#">Project Management</a></li>
                    <li class="mb-2"><a href="#">Local Jobs</a></li>
                </ul>
            </div>
            <div class="col-lg-2 col-md-6 mb-4">
                <h6 class="fw-bold mb-3 text-white">About</h6>
                <ul class="list-unstyled small">
                    <li class="mb-2"><a href="#">About Us</a></li>
                    <li class="mb-2"><a href="#">How it Works</a></li>
                    <li class="mb-2"><a href="#">Security</a></li>
                    <li class="mb-2"><a href="#">Investor</a></li>
                    <li class="mb-2"><a href="#">News</a></li>
                </ul>
            </div>
            <div class="col-lg-2 col-md-6 mb-4">
                <h6 class="fw-bold mb-3 text-white">Terms</h6>
                <ul class="list-unstyled small">
                    <li class="mb-2"><a href="#">Privacy Policy</a></li>
                    <li class="mb-2"><a href="#">Terms and Conditions</a></li>
                    <li class="mb-2"><a href="#">Copyright Policy</a></li>
                    <li class="mb-2"><a href="#">Code of Conduct</a></li>
                    <li class="mb-2"><a href="#">Fees and Charges</a></li>
                </ul>
            </div>
            <div class="col-lg-2 col-md-6 mb-4">
                <h6 class="fw-bold mb-3 text-white">Apps</h6>
                <div class="d-flex flex-column gap-2">
                    <a href="#" class="btn btn-outline-light btn-sm text-start">
                        <i class="fab fa-apple me-2"></i> App Store
                    </a>
                    <a href="#" class="btn btn-outline-light btn-sm text-start">
                        <i class="fab fa-google-play me-2"></i> Google Play
                    </a>
                </div>
            </div>
        </div>
        <hr class="border-secondary my-4 opacity-25">
        <div class="row align-items-center">
            <div class="col-md-6 text-center text-md-start">
                <p class="mb-0 text-muted small">&copy; <?php echo date('Y'); ?> InkMyBook International Ltd. All rights
                    reserved.</p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <span class="text-muted small"><i class="fas fa-globe me-1"></i> English (US)</span>
            </div>
        </div>
    </div>
</footer>

<!-- Chat Widget -->
<link rel="stylesheet" href="/inkmybook/assets/css/chat_widget.css?v=<?php echo time(); ?>">
<?php include $_SERVER['DOCUMENT_ROOT'] . '/inkmybook/includes/chat_widget.php'; ?>
<script src="/inkmybook/assets/js/chat_widget.js"></script>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="/inkmybook/assets/js/script.js"></script>
</body>

</html>