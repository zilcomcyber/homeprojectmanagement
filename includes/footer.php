

    <!-- Modern Footer -->
    <footer class="relative mt-20 overflow-hidden">
        <!-- Footer Background with Gradient -->
        <div class="absolute inset-0 bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900"></div>
        <div class="absolute inset-0 bg-gradient-to-r from-blue-900/20 via-purple-900/20 to-green-900/20"></div>
        
        <!-- Animated Background Elements -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute -top-40 -right-40 w-80 h-80 bg-blue-500/10 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-purple-500/10 rounded-full blur-3xl animate-pulse" style="animation-delay: 2s;"></div>
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-60 h-60 bg-green-500/10 rounded-full blur-3xl animate-pulse" style="animation-delay: 4s;"></div>
        </div>

        <div class="relative z-10">
            <div class="max-w-7xl mx-auto px-4 py-16 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 lg:gap-8">
                    <!-- Brand Section -->
                    <div class="lg:col-span-2">
                        <div class="flex items-center space-x-4 mb-6">
                            <div class="relative">
                                <img src="<?php echo BASE_URL; ?>generated-icon.png" alt="County Logo" class="h-12 w-12 rounded-xl shadow-lg">
                                <div class="absolute inset-0 rounded-xl bg-gradient-to-br from-blue-400/20 to-purple-500/20"></div>
                            </div>
                            <div>
                                <h3 class="text-2xl font-bold text-white"><?php echo APP_NAME; ?></h3>
                                <p class="text-gray-300">Migori County Government</p>
                            </div>
                        </div>
                        <p class="text-gray-300 mb-8 max-w-md leading-relaxed">
                            Promoting transparency and accountability in county development projects. 
                            Stay informed about ongoing and completed projects that are transforming our communities.
                        </p>
                        <div class="flex space-x-4">
                            <a href="#" class="group p-3 bg-white/10 hover:bg-white/20 rounded-xl transition-all duration-300 backdrop-blur-sm">
                                <i class="fab fa-facebook-f text-gray-300 group-hover:text-blue-400 transition-colors duration-300"></i>
                            </a>
                            <a href="#" class="group p-3 bg-white/10 hover:bg-white/20 rounded-xl transition-all duration-300 backdrop-blur-sm">
                                <i class="fab fa-twitter text-gray-300 group-hover:text-blue-400 transition-colors duration-300"></i>
                            </a>
                            <a href="#" class="group p-3 bg-white/10 hover:bg-white/20 rounded-xl transition-all duration-300 backdrop-blur-sm">
                                <i class="fab fa-instagram text-gray-300 group-hover:text-pink-400 transition-colors duration-300"></i>
                            </a>
                            <a href="#" class="group p-3 bg-white/10 hover:bg-white/20 rounded-xl transition-all duration-300 backdrop-blur-sm">
                                <i class="fab fa-linkedin text-gray-300 group-hover:text-blue-500 transition-colors duration-300"></i>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Quick Links -->
                    <div>
                        <h4 class="text-lg font-semibold text-white mb-6 flex items-center">
                            <i class="fas fa-link mr-2 text-blue-400"></i>
                            Quick Links
                        </h4>
                        <ul class="space-y-3">
                            <li>
                                <a href="<?php echo BASE_URL; ?>index.php" 
                                   class="text-gray-300 hover:text-blue-400 transition-colors duration-300 flex items-center group">
                                    <i class="fas fa-chevron-right mr-2 text-xs opacity-0 group-hover:opacity-100 transition-all duration-300 transform group-hover:translate-x-1"></i>
                                    All Projects
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo BASE_URL; ?>index.php?status=ongoing" 
                                   class="text-gray-300 hover:text-blue-400 transition-colors duration-300 flex items-center group">
                                    <i class="fas fa-chevron-right mr-2 text-xs opacity-0 group-hover:opacity-100 transition-all duration-300 transform group-hover:translate-x-1"></i>
                                    Ongoing Projects
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo BASE_URL; ?>index.php?status=completed" 
                                   class="text-gray-300 hover:text-blue-400 transition-colors duration-300 flex items-center group">
                                    <i class="fas fa-chevron-right mr-2 text-xs opacity-0 group-hover:opacity-100 transition-all duration-300 transform group-hover:translate-x-1"></i>
                                    Completed Projects
                                </a>
                            </li>
                            <?php if (isset($_SESSION['admin']) && $_SESSION['admin']): ?>
                            <li>
                                <a href="<?php echo BASE_URL; ?>admin/feedback.php" 
                                   class="text-gray-300 hover:text-blue-400 transition-colors duration-300 flex items-center group">
                                    <i class="fas fa-chevron-right mr-2 text-xs opacity-0 group-hover:opacity-100 transition-all duration-300 transform group-hover:translate-x-1"></i>
                                    Citizen Feedback
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    
                    <!-- Contact Info -->
                    <div>
                        <h4 class="text-lg font-semibold text-white mb-6 flex items-center">
                            <i class="fas fa-address-book mr-2 text-green-400"></i>
                            Contact Us
                        </h4>
                        <ul class="space-y-4 text-gray-300">
                            <li class="flex items-start space-x-3 group">
                                <div class="flex-shrink-0 p-2 bg-blue-500/20 rounded-lg group-hover:bg-blue-500/30 transition-colors duration-300">
                                    <i class="fas fa-map-marker-alt text-blue-400"></i>
                                </div>
                                <div>
                                    <div class="font-medium text-white">Migori County Government</div>
                                    <div class="text-sm">P.O. Box 151, Migori</div>
                                </div>
                            </li>
                            <li class="flex items-center space-x-3 group">
                                <div class="flex-shrink-0 p-2 bg-green-500/20 rounded-lg group-hover:bg-green-500/30 transition-colors duration-300">
                                    <i class="fas fa-phone text-green-400"></i>
                                </div>
                                <span>+254 020 123 4567</span>
                            </li>
                            <li class="flex items-center space-x-3 group">
                                <div class="flex-shrink-0 p-2 bg-purple-500/20 rounded-lg group-hover:bg-purple-500/30 transition-colors duration-300">
                                    <i class="fas fa-envelope text-purple-400"></i>
                                </div>
                                <span>info@migori.go.ke</span>
                            </li>
                            <li class="flex items-center space-x-3 group">
                                <div class="flex-shrink-0 p-2 bg-orange-500/20 rounded-lg group-hover:bg-orange-500/30 transition-colors duration-300">
                                    <i class="fas fa-globe text-orange-400"></i>
                                </div>
                                <span>www.migori.go.ke</span>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <!-- Newsletter Section -->
                <div class="mt-12 p-8 bg-white/5 backdrop-blur-sm rounded-2xl border border-white/10">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                        <div>
                            <h4 class="text-xl font-semibold text-white mb-2">Stay Updated</h4>
                            <p class="text-gray-300">Get the latest updates on county development projects delivered to your inbox.</p>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-3 min-w-96">
                            <input type="email" placeholder="Enter your email address" 
                                   class="flex-1 px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent backdrop-blur-sm">
                            <button class="btn-modern btn-primary-modern whitespace-nowrap">
                                <i class="fas fa-paper-plane"></i>
                                Subscribe
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Bottom Section -->
                <div class="border-t border-white/10 mt-12 pt-8 flex flex-col md:flex-row justify-between items-center gap-6">
                    <p class="text-gray-300 text-center md:text-left">
                        © <?php echo date('Y'); ?> Migori County Government. All rights reserved.
                    </p>
                    <div class="flex flex-wrap justify-center md:justify-end gap-6">
                        <a href="#" class="text-gray-300 hover:text-blue-400 transition-colors duration-300">
                            Privacy Policy
                        </a>
                        <a href="#" class="text-gray-300 hover:text-blue-400 transition-colors duration-300">
                            Terms of Service
                        </a>
                        <a href="#" class="text-gray-300 hover:text-blue-400 transition-colors duration-300">
                            Accessibility
                        </a>
                        <a href="#" class="text-gray-300 hover:text-blue-400 transition-colors duration-300">
                            Cookie Policy
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <!-- Leaflet JS for Maps -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" 
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    
    <!-- Main Application JS -->
    <script src="<?php echo BASE_URL; ?>assets/js/app.js"></script>
    
    <?php if (isset($is_admin_page) && $is_admin_page): ?>
    <!-- Admin-specific JS -->
    <script src="<?php echo BASE_URL; ?>assets/js/admin.js"></script>
    <?php endif; ?>
    
    <?php if (isset($additional_js)): ?>
        <?php foreach ($additional_js as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

    <script>
        // Mobile menu toggle
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            const mobileMenu = document.getElementById('mobile-menu');
            mobileMenu.classList.toggle('hidden');
        });

        // Theme toggle functionality
        document.getElementById('theme-toggle').addEventListener('click', function() {
            const html = document.documentElement;
            const isDark = html.classList.contains('dark');
            
            if (isDark) {
                html.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            } else {
                html.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            }
        });

        // Initialize theme from localStorage
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            
            if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
                document.documentElement.classList.add('dark');
            }
        });

        // Header background opacity on scroll
        window.addEventListener('scroll', function() {
            const header = document.querySelector('header');
            const scrolled = window.pageYOffset;
            const rate = scrolled * -0.5;
            
            if (scrolled > 50) {
                header.style.background = 'rgba(255, 255, 255, 0.95)';
                header.style.backdropFilter = 'blur(20px)';
            } else {
                header.style.background = 'rgba(255, 255, 255, 0.25)';
                header.style.backdropFilter = 'blur(20px)';
            }
        });
    </script>

</body>
</html>

