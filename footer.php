<footer class="bg-dark text-white pt-5 pb-4 mt-5">
    <div class="container text-center text-md-start">
        <div class="row">
            <!-- Company Info -->
            <div class="col-md-4 col-lg-4 col-xl-4 mx-auto mt-3">
                <h5 class="text-uppercase mb-4 fw-bold text-warning">OM RUDRA BOX MANUFACTURING</h5>
                <p>Welcome to Om Rudra Box Manufacturing, your trusted partner for high-quality packaging solutions. We specialize in corrugated boxes and custom packaging tailored to your needs.</p>
            </div>

            <!-- Quick Links -->
            <div class="col-md-2 col-lg-2 col-xl-2 mx-auto mt-3">
                <h5 class="text-uppercase mb-4 fw-bold text-warning">Quick Links</h5>
                <p><a href="index.php" class="text-white text-decoration-none">Home</a></p>
                <p><a href="products.php" class="text-white text-decoration-none">Products</a></p>
                <p><a href="custom_box.php" class="text-white text-decoration-none">Custom Box</a></p>
                <p><a href="consultations.php" class="text-white text-decoration-none">Consultation</a></p>
            </div>

            <!-- Account Links -->
            <div class="col-md-2 col-lg-2 col-xl-2 mx-auto mt-3">
                <h5 class="text-uppercase mb-4 fw-bold text-warning">User Links</h5>
                <p><a href="cart.php" class="text-white text-decoration-none">Cart</a></p>
                <p><a href="wishlist.php" class="text-white text-decoration-none">Wishlist</a></p>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <p><a href="profile.php" class="text-white text-decoration-none">Profile</a></p>
                    <p><a href="orders.php" class="text-white text-decoration-none">My Orders</a></p>
                <?php else: ?>
                    <p><a href="login.php" class="text-white text-decoration-none">Login</a></p>
                    <p><a href="register.php" class="text-white text-decoration-none">Register</a></p>
                <?php endif; ?>
            </div>

            <!-- Contact -->
            <div class="col-md-4 col-lg-3 col-xl-3 mx-auto mt-3">
                <h5 class="text-uppercase mb-4 fw-bold text-warning">Contact</h5>
                <p><i class="fas fa-home me-3 text-warning"></i> Odhav, Ahmedabad, Gujarat, India</p>
                <p><i class="fas fa-phone me-3 text-warning"></i> +91 9712843487</p>
                <p><a href="contact.php" class="text-warning text-decoration-none fw-bold">Send us a message</a></p>
            </div>
        </div>

        <hr class="mb-4 mt-4">

        <div class="row align-items-center">
            <div class="col-md-7 col-lg-8 text-center text-md-start">
                <p class="mb-0">&copy; <?php echo date("Y"); ?> <strong>OM RUDRA BOX MANUFACTURING</strong>. All Rights Reserved.</p>
            </div>
            <div class="col-md-5 col-lg-4 text-center text-md-end mt-2 mt-md-0">
                <a href="#" class="text-white me-3"><i class="fab fa-facebook-f"></i></a>
                <a href="#" class="text-white me-3"><i class="fab fa-twitter"></i></a>
                <a href="#" class="text-white me-3"><i class="fab fa-instagram"></i></a>
                <a href="#" class="text-white"><i class="fab fa-linkedin"></i></a>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
