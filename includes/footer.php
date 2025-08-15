        <footer class="footer">
            <p>Made With <i class="fas fa-heart"></i> Premiumisme.</p>
        </footer>
    </main>

    <!-- Notifikasi Toast -->
    <div id="toast" class="toast" style="display: none; transform: translateX(120%);">
        <span id="toastMessage"></span>
    </div>

    <script>
        // Mobile Navigation Toggle
        document.addEventListener('DOMContentLoaded', function() {
            const hamburgerBtn = document.getElementById('hamburger-btn');
            const mobileNav = document.getElementById('mobile-nav');
            
            hamburgerBtn.addEventListener('click', function() {
                hamburgerBtn.classList.toggle('active');
                mobileNav.classList.toggle('active');
                document.body.classList.toggle('nav-open');
            });

            // Close mobile nav when clicking on a link
            const mobileNavLinks = document.querySelectorAll('.mobile-nav-link');
            mobileNavLinks.forEach(link => {
                link.addEventListener('click', function() {
                    hamburgerBtn.classList.remove('active');
                    mobileNav.classList.remove('active');
                    document.body.classList.remove('nav-open');
                });
            });

            // Close mobile nav when clicking outside
            document.addEventListener('click', function(e) {
                if (!hamburgerBtn.contains(e.target) && !mobileNav.contains(e.target)) {
                    hamburgerBtn.classList.remove('active');
                    mobileNav.classList.remove('active');
                    document.body.classList.remove('nav-open');
                }
            });
        });

        // Toast Notification Function
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            const toastMessage = document.getElementById('toastMessage');
            // Use textContent to prevent XSS
            toastMessage.textContent = message;
            
            // Set background color based on type
            let bgColor;
            switch(type) {
                case 'error':
                    bgColor = 'rgba(239, 68, 68, 0.7)';
                    break;
                case 'info':
                    bgColor = 'rgba(59, 130, 246, 0.7)';
                    break;
                default:
                    bgColor = 'rgba(34, 197, 94, 0.7)';
            }
            
            toast.style.backgroundColor = bgColor;
            toast.style.transform = 'translateX(0)';
            toast.style.opacity = '1';
            toast.style.pointerEvents = 'auto';
            setTimeout(() => { 
                toast.style.transform = 'translateX(120%)';
                toast.style.opacity = '0';
                toast.style.pointerEvents = 'none';
            }, 3000);
        }
    </script>
</body>
</html>
