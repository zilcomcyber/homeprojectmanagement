
    <!-- Footer -->
    <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- About Section -->
                <div class="col-span-1 md:col-span-2">
                    <div class="flex items-center space-x-3 mb-4">
                        <img src="<?php echo BASE_URL; ?>generated-icon.png" alt="County Logo" class="h-8 w-8">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white"><?php echo APP_NAME; ?></h3>
                    </div>
                    <p class="text-gray-600 dark:text-gray-400 mb-4 max-w-md">
                        Promoting transparency and accountability in county development projects. 
                        Stay informed about ongoing and completed projects in Migori County.
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                            <i class="fab fa-linkedin"></i>
                        </a>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div>
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-4">Quick Links</h4>
                    <ul class="space-y-2">
                        <li>
                            <a href="<?php echo BASE_URL; ?>index.php" class="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                                All Projects
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>index.php?status=ongoing" class="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                                Ongoing Projects
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>index.php?status=completed" class="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                                Completed Projects
                            </a>
                        </li>
                        <?php if (isset($_SESSION['admin']) && $_SESSION['admin']): ?>
                        <li>
                            <a href="<?php echo BASE_URL; ?>admin/feedback.php" class="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                                Citizen Feedback
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <!-- Contact Info -->
                <div>
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-4">Contact</h4>
                    <ul class="space-y-2 text-gray-600 dark:text-gray-400">
                        <li class="flex items-start space-x-2">
                            <i class="fas fa-map-marker-alt mt-1 text-blue-600"></i>
                            <span>Migori County Government<br>P.O. Box 151, Migori</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <i class="fas fa-phone text-blue-600"></i>
                            <span>+254 020 123 4567</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <i class="fas fa-envelope text-blue-600"></i>
                            <span>info@migori.go.ke</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <i class="fas fa-globe text-blue-600"></i>
                            <span>www.migori.go.ke</span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Bottom Section -->
            <div class="border-t border-gray-200 dark:border-gray-700 mt-8 pt-8 flex flex-col md:flex-row justify-between items-center">
                <p class="text-gray-600 dark:text-gray-400 text-sm">
                    © <?php echo date('Y'); ?> Migori County Government. All rights reserved.
                </p>
                <div class="flex space-x-6 mt-4 md:mt-0">
                    <a href="#" class="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 text-sm transition-colors">
                        Privacy Policy
                    </a>
                    <a href="#" class="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 text-sm transition-colors">
                        Terms of Service
                    </a>
                    <a href="#" class="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 text-sm transition-colors">
                        Accessibility
                    </a>
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

</body>
</html>
