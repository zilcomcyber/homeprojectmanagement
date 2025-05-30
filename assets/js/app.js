// Main application JavaScript for County Project Tracking System

// Theme Management
class ThemeManager {
    constructor() {
        this.init();
    }

    init() {
        // Check for saved theme preference or default to light mode
        const savedTheme = localStorage.getItem('theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

        if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
            this.enableDarkMode();
        } else {
            this.enableLightMode();
        }

        // Theme toggle button
        const themeToggle = document.getElementById('theme-toggle');
        if (themeToggle) {
            themeToggle.addEventListener('click', () => this.toggleTheme());
        }
    }

    enableDarkMode() {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    }

    enableLightMode() {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    }

    toggleTheme() {
        if (document.documentElement.classList.contains('dark')) {
            this.enableLightMode();
        } else {
            this.enableDarkMode();
        }
    }
}

// Utility Functions
class Utils {
    static showNotification(message, type = 'info', duration = 5000) {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm transform transition-all duration-300 translate-x-full`;

        const typeClasses = {
            success: 'bg-green-100 border-green-500 text-green-800 border-l-4',
            error: 'bg-red-100 border-red-500 text-red-800 border-l-4',
            warning: 'bg-yellow-100 border-yellow-500 text-yellow-800 border-l-4',
            info: 'bg-blue-100 border-blue-500 text-blue-800 border-l-4'
        };

        notification.className += ` ${typeClasses[type] || typeClasses.info}`;

        notification.innerHTML = `
            <div class="flex items-center">
                <div class="flex-1">
                    <p class="font-medium">${message}</p>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-3 text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;

        document.body.appendChild(notification);

        // Animate in
        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 100);

        // Auto remove
        setTimeout(() => {
            notification.classList.add('translate-x-full');
            setTimeout(() => notification.remove(), 300);
        }, duration);
    }

    static formatCurrency(amount) {
        return new Intl.NumberFormat('en-KE', {
            style: 'currency',
            currency: 'KES',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(amount);
    }

    static formatDate(dateString) {
        return new Date(dateString).toLocaleDateString('en-KE', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }

    static debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
}

// Project Management
class ProjectManager {
    constructor() {
        this.currentProject = null;
    }

    async fetchProjectDetails(projectId) {
        try {
            const response = await fetch(`${window.BASE_URL}api/projects.php?id=${projectId}`);
            const data = await response.json();

            if (data.success) {
                return data.project;
            } else {
                throw new Error(data.message || 'Failed to fetch project details');
            }
        } catch (error) {
            console.error('Error fetching project details:', error);
            Utils.showNotification('Failed to load project details', 'error');
            return null;
        }
    }

    async showProjectDetails(projectId) {
        const project = await this.fetchProjectDetails(projectId);
        if (!project) return;

        this.currentProject = project;

        const detailsContainer = document.getElementById('projectDetails');
        detailsContainer.innerHTML = this.renderProjectDetails(project);

        document.getElementById('projectModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    renderProjectDetails(project) {
        const progressColor = this.getProgressColor(project.progress_percentage);
        const statusBadge = this.getStatusBadgeClass(project.status);

        return `
            <div class="space-y-6">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                            ${project.project_name}
                        </h3>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${statusBadge}">
                            ${project.status.charAt(0).toUpperCase() + project.status.slice(1)}
                        </span>
                    </div>
                    <button onclick="projectManager.exportProjectPDF(${project.id})" 
                            class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                        <i class="fas fa-file-pdf mr-2"></i>
                        Export PDF
                    </button>
                </div>

                <div class="prose dark:prose-invert">
                    <p class="text-gray-600 dark:text-gray-300">${project.description || 'No description available'}</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <h4 class="font-semibold text-gray-900 dark:text-white">Project Information</h4>
                        <dl class="space-y-3">
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Department</dt>
                                <dd class="text-sm text-gray-900 dark:text-white">${project.department_name}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Location</dt>
                                <dd class="text-sm text-gray-900 dark:text-white">${project.ward_name}, ${project.sub_county_name}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Budget</dt>
                                <dd class="text-sm text-gray-900 dark:text-white">${Utils.formatCurrency(project.budget)}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Year</dt>
                                <dd class="text-sm text-gray-900 dark:text-white">${project.project_year}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="space-y-4">
                        <h4 class="font-semibold text-gray-900 dark:text-white">Timeline & Progress</h4>
                        <dl class="space-y-3">
                            ${project.start_date ? `
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Start Date</dt>
                                    <dd class="text-sm text-gray-900 dark:text-white">${Utils.formatDate(project.start_date)}</dd>
                                </div>
                            ` : ''}
                            ${project.expected_completion_date ? `
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Expected Completion</dt>
                                    <dd class="text-sm text-gray-900 dark:text-white">${Utils.formatDate(project.expected_completion_date)}</dd>
                                </div>
                            ` : ''}
                            ${project.actual_completion_date ? `
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Actual Completion</dt>
                                    <dd class="text-sm text-gray-900 dark:text-white">${Utils.formatDate(project.actual_completion_date)}</dd>
                                </div>
                            ` : ''}
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Progress</dt>
                                <dd class="mt-1">
                                    <div class="flex items-center">
                                        <div class="flex-1">
                                            <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                                <div class="h-2 rounded-full ${progressColor}" style="width: ${project.progress_percentage}%"></div>
                                            </div>
                                        </div>
                                        <span class="ml-3 text-sm text-gray-900 dark:text-white">${project.progress_percentage}%</span>
                                    </div>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Rating</dt>
                                <dd class="mt-1">
                                    <div class="flex items-center space-x-1">
                                        ${this.generateStarRating(project.average_rating)}
                                        <span class="text-sm text-gray-600 dark:text-gray-400">${parseFloat(project.average_rating).toFixed(1)} (${project.total_ratings})</span>
                                    </div>
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                ${project.contractor_name ? `
                    <div class="space-y-4">
                        <h4 class="font-semibold text-gray-900 dark:text-white">Contractor Information</h4>
                        <dl class="space-y-3">
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Contractor</dt>
                                <dd class="text-sm text-gray-900 dark:text-white">${project.contractor_name}</dd>
                            </div>
                            ${project.contractor_contact ? `
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Contact</dt>
                                    <dd class="text-sm text-gray-900 dark:text-white">${project.contractor_contact}</dd>
                                </div>
                            ` : ''}
                        </dl>
                    </div>
                ` : ''}

                ${project.location_coordinates ? `
                    <div class="space-y-4">
                        <h4 class="font-semibold text-gray-900 dark:text-white">Location</h4>
                        <div id="projectDetailMap" class="h-48 bg-gray-200 dark:bg-gray-700 rounded-lg"></div>
                    </div>
                ` : ''}
            </div>
        `;
    }

    getProgressColor(percentage) {
        if (percentage >= 80) return 'bg-green-500';
        if (percentage >= 60) return 'bg-blue-500';
        if (percentage >= 40) return 'bg-yellow-500';
        if (percentage >= 20) return 'bg-orange-500';
        return 'bg-red-500';
    }

    generateStarRating(rating) {
        const fullStars = Math.floor(rating);
        const halfStar = (rating - fullStars) >= 0.5;
        const emptyStars = 5 - fullStars - (halfStar ? 1 : 0);

        let stars = '';

        // Full stars
        for (let i = 0; i < fullStars; i++) {
            stars += '<i class="fas fa-star text-yellow-400"></i>';
        }

        // Half star
        if (halfStar) {
            stars += '<i class="fas fa-star-half-alt text-yellow-400"></i>';
        }

        // Empty stars
        for (let i = 0; i < emptyStars; i++) {
            stars += '<i class="far fa-star text-gray-300"></i>';
        }

        return `<div class="flex space-x-1">${stars}</div>`;
    }

    getStatusBadgeClass(status) {
        const classes = {
            planning: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
            ongoing: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
            completed: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
            suspended: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
            cancelled: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
        };
        return classes[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300';
    }

    closeProjectDetails() {
        document.getElementById('projectModal').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    async exportProjectPDF(projectId) {
        try {
            const response = await fetch(`${window.BASE_URL}api/export_pdf.php?project_id=${projectId}`);

            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = url;
                a.download = `project_${projectId}_details.pdf`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                Utils.showNotification('PDF exported successfully', 'success');
            } else {
                throw new Error('Export failed');
            }
        } catch (error) {
            console.error('Export error:', error);
            Utils.showNotification('Failed to export PDF', 'error');
        }
    }
}

// Feedback Management
class FeedbackManager {
    constructor() {
        this.currentProjectId = null;
    }

    showFeedbackForm(projectId) {
        this.currentProjectId = projectId;
        document.getElementById('feedbackProjectId').value = projectId;
        document.getElementById('feedbackModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    closeFeedbackForm() {
        document.getElementById('feedbackModal').classList.add('hidden');
        document.getElementById('feedbackForm').reset();
        document.body.style.overflow = 'auto';
    }

    async submitFeedback(event) {
        event.preventDefault();

        const formData = new FormData(event.target);

        try {
            const response = await fetch(`${window.BASE_URL}api/feedback.php`, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                Utils.showNotification('Feedback submitted successfully', 'success');
                this.closeFeedbackForm();
            } else {
                Utils.showNotification(data.message || 'Failed to submit feedback', 'error');
            }
        } catch (error) {
            console.error('Feedback submission error:', error);
            Utils.showNotification('Failed to submit feedback', 'error');
        }
    }
}

// Map Management
class MapManager {
    constructor() {
        this.map = null;
        this.markers = [];
        this.isMapVisible = false;
    }

    async showMap() {
        try {
            document.getElementById('mapModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            this.isMapVisible = true;

            if (!this.map) {
                await this.initializeMap();
            }

            await this.loadProjectMarkers();

            setTimeout(() => {
                if (this.map) {
                    this.map.invalidateSize();
                }
            }, 100);

        } catch (error) {
            console.error('Error showing map:', error);
            Utils.showNotification('Failed to load map', 'error');
        }
    }

    async initializeMap() {
        try {
            const defaultLat = -1.2921;
            const defaultLng = 36.8219;

            this.map = L.map('map').setView([defaultLat, defaultLng], 7);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 18
            }).addTo(this.map);

            L.control.scale().addTo(this.map);

        } catch (error) {
            console.error('Error initializing map:', error);
            throw error;
        }
    }

    async loadProjectMarkers() {
        try {
            this.clearMarkers();

            const urlParams = new URLSearchParams(window.location.search);
            const params = urlParams.toString();

            const response = await fetch(`${window.BASE_URL}api/projects.php?map=1&${params}`);
            const data = await response.json();

            if (!data.success) {
                throw new Error(data.message || 'Failed to load projects');
            }

            const projects = data.projects || [];
            const validProjects = projects.filter(project => 
                project.location_coordinates && 
                project.location_coordinates.includes(',')
            );

            if (validProjects.length === 0) {
                Utils.showNotification('No projects with location data found', 'warning');
                return;
            }

            validProjects.forEach(project => {
                this.addProjectMarker(project);
            });

            if (this.markers.length > 0) {
                const group = new L.featureGroup(this.markers);
                this.map.fitBounds(group.getBounds().pad(0.1));
            }

        } catch (error) {
            console.error('Error loading project markers:', error);
            Utils.showNotification('Failed to load project locations', 'error');
        }
    }

    addProjectMarker(project) {
        try {
            const [lat, lng] = project.location_coordinates.split(',').map(coord => parseFloat(coord.trim()));

            if (isNaN(lat) || isNaN(lng)) {
                console.warn(`Invalid coordinates for project ${project.id}: ${project.location_coordinates}`);
                return;
            }

            const markerColor = this.getMarkerColor(project.status);

            const markerIcon = L.divIcon({
                className: 'custom-marker',
                html: `<div class="w-6 h-6 rounded-full border-2 border-white shadow-lg ${markerColor} flex items-center justify-center">
                         <i class="fas fa-map-marker-alt text-white text-xs"></i>
                       </div>`,
                iconSize: [24, 24],
                iconAnchor: [12, 12]
            });

            const marker = L.marker([lat, lng], { icon: markerIcon });

            const popupContent = this.createPopupContent(project);
            marker.bindPopup(popupContent, {
                maxWidth: 300,
                className: 'custom-popup'
            });

            marker.addTo(this.map);
            this.markers.push(marker);

        } catch (error) {
            console.error(`Error adding marker for project ${project.id}:`, error);
        }
    }

    getMarkerColor(status) {
        const colors = {
            planning: 'bg-yellow-500',
            ongoing: 'bg-blue-500',
            completed: 'bg-green-500',
            suspended: 'bg-orange-500',
            cancelled: 'bg-red-500'
        };
        return colors[status] || 'bg-gray-500';
    }

    createPopupContent(project) {
        const statusBadge = this.getStatusBadgeClass(project.status);

        return `
            <div class="p-2 min-w-0">
                <div class="flex items-start justify-between mb-2">
                    <h4 class="font-semibold text-gray-900 text-sm leading-tight pr-2">
                        ${this.escapeHtml(project.project_name)}
                    </h4>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${statusBadge} whitespace-nowrap">
                        ${project.status.charAt(0).toUpperCase() + project.status.slice(1)}
                    </span>
                </div>

                <div class="space-y-1 text-xs text-gray-600">
                    <div>
                        <span class="font-medium">Department:</span> ${this.escapeHtml(project.department_name)}
                    </div>
                    <div>
                        <span class="font-medium">Location:</span> ${this.escapeHtml(project.ward_name)}, ${this.escapeHtml(project.sub_county_name)}
                    </div>
                    <div>
                        <span class="font-medium">Budget:</span> ${Utils.formatCurrency(project.budget)}
                    </div>
                    ${project.progress_percentage > 0 ? `
                        <div class="mt-2">
                            <div class="flex items-center justify-between mb-1">
                                <span class="font-medium">Progress:</span>
                                <span>${project.progress_percentage}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-1.5">
                                <div class="h-1.5 rounded-full ${this.getProgressColor(project.progress_percentage)}" 
                                     style="width: ${project.progress_percentage}%"></div>
                            </div>
                        </div>
                    ` : ''}
                </div>
                 <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Rating</dt>
                                <dd class="mt-1">
                                    <div class="flex items-center space-x-1">
                                        ${this.generateStarRating(project.average_rating)}
                                        <span class="text-sm text-gray-600 dark:text-gray-400">${parseFloat(project.average_rating).toFixed(1)} (${project.total_ratings})</span>
                                    </div>
                                </dd>
                            </div>

                <div class="flex space-x-2 mt-3">
                    <button onclick="showProjectDetails(${project.id}); mapManager.closeMap();" 
                            class="flex-1 bg-blue-600 text-white text-xs px-2 py-1 rounded hover:bg-blue-700 transition-colors">
                        <i class="fas fa-eye mr-1"></i>Details
                    </button>
                    <button onclick="showFeedbackForm(${project.id}); mapManager.closeMap();" 
                            class="flex-1 bg-green-600 text-white text-xs px-2 py-1 rounded hover:bg-green-700 transition-colors">
                        <i class="fas fa-comment mr-1"></i>Feedback
                    </button>
                </div>
            </div>
        `;
    }

    getStatusBadgeClass(status) {
        const classes = {
            planning: 'bg-yellow-100 text-yellow-800',
            ongoing: 'bg-blue-100 text-blue-800',
            completed: 'bg-green-100 text-green-800',
            suspended: 'bg-orange-100 text-orange-800',
            cancelled: 'bg-red-100 text-red-800'
        };
        return classes[status] || 'bg-gray-100 text-gray-800';
    }

    getProgressColor(percentage) {
        if (percentage >= 80) return 'bg-green-500';
        if (percentage >= 60) return 'bg-blue-500';
        if (percentage >= 40) return 'bg-yellow-500';
        if (percentage >= 20) return 'bg-orange-500';
        return 'bg-red-500';
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

     generateStarRating(rating) {
        const fullStars = Math.floor(rating);
        const halfStar = (rating - fullStars) >= 0.5;
        const emptyStars = 5 - fullStars - (halfStar ? 1 : 0);

        let stars = '';

        // Full stars
        for (let i = 0; i < fullStars; i++) {
            stars += '<i class="fas fa-star text-yellow-400"></i>';
        }

        // Half star
        if (halfStar) {
            stars += '<i class="fas fa-star-half-alt text-yellow-400"></i>';
        }

        // Empty stars
        for (let i = 0; i < emptyStars; i++) {
            stars += '<i class="far fa-star text-gray-300"></i>';
        }

        return `<div class="flex space-x-1">${stars}</div>`;
    }

    clearMarkers() {
        this.markers.forEach(marker => {
            this.map.removeLayer(marker);
        });
        this.markers = [];
    }

    closeMap() {
        document.getElementById('mapModal').classList.add('hidden');
        document.body.style.overflow = 'auto';
        this.isMapVisible = false;
    }
}

// Export Functions
class ExportManager {
    static async exportPDF() {
        try {
            const urlParams = new URLSearchParams(window.location.search);
            const queryString = urlParams.toString();

            const response = await fetch(`${window.BASE_URL}api/export_pdf.php?${queryString}`);

            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = url;
                a.download = `county_projects_${new Date().getTime()}.pdf`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                Utils.showNotification('PDF exported successfully', 'success');
            } else {
                throw new Error('Export failed');
            }
        } catch (error) {
            console.error('Export error:', error);
            Utils.showNotification('Failed to export PDF', 'error');
        }
    }

    static async exportCSV() {
        try {
            const urlParams = new URLSearchParams(window.location.search);
            const queryString = urlParams.toString();

            const response = await fetch(`${window.BASE_URL}api/export_csv.php?${queryString}`);

            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = url;
                a.download = `county_projects_${new Date().getTime()}.csv`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                Utils.showNotification('CSV exported successfully', 'success');
            } else {
                throw new Error('Export failed');
            }
        } catch (error) {
            console.error('Export error:', error);
            Utils.showNotification('Failed to export CSV', 'error');
        }
    }
}

// Index Page Management
class IndexPageManager {
    constructor() {
        this.map = null;
        this.markers = [];
        this.allProjects = [];
        this.currentView = 'grid';
    }

    init() {
        this.loadProjects();
        this.initializeFilters();
    }

    switchView(viewType) {
        this.currentView = viewType;

        // Update button states
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.classList.remove('bg-blue-600', 'text-white');
            btn.classList.add('bg-gray-200', 'text-gray-700');
        });

        // Hide all containers
        document.getElementById('gridContainer').classList.add('hidden');
        document.getElementById('listContainer').classList.add('hidden');
        document.getElementById('mapContainer').classList.add('hidden');

        // Show selected view
        if (viewType === 'grid') {
            document.getElementById('gridView').classList.remove('bg-gray-200', 'text-gray-700');
            document.getElementById('gridView').classList.add('bg-blue-600', 'text-white');
            document.getElementById('gridContainer').classList.remove('hidden');
            this.displayProjectsGrid();
        } else if (viewType === 'list') {
            document.getElementById('listView').classList.remove('bg-gray-200', 'text-gray-700');
            document.getElementById('listView').classList.add('bg-blue-600', 'text-white');
            document.getElementById('listContainer').classList.remove('hidden');
            this.displayProjectsList();
        } else if (viewType === 'map') {
            document.getElementById('mapView').classList.remove('bg-gray-200', 'text-gray-700');
            document.getElementById('mapView').classList.add('bg-blue-600', 'text-white');
            document.getElementById('mapContainer').classList.remove('hidden');
            this.initializeMap();
        }
    }

    async loadProjects() {
        const params = new URLSearchParams();

        // Get filter values
        const wardFilter = document.getElementById('ward_filter')?.value;
        const statusFilter = document.getElementById('status_filter')?.value;

        if (wardFilter) params.append('ward', wardFilter);
        if (statusFilter) params.append('status', statusFilter);

        try {
            const response = await fetch(`${window.BASE_URL}api/projects.php?${params}`);
            const data = await response.json();

            if (data.success) {
                this.allProjects = data.projects;
                this.updateProjectsDisplay();
            } else {
                console.error('Failed to load projects:', data.message);
            }
        } catch (error) {
            console.error('Error loading projects:', error);
        }
    }

    updateProjectsDisplay() {
        if (this.currentView === 'grid') {
            this.displayProjectsGrid();
        } else if (this.currentView === 'list') {
            this.displayProjectsList();
        } else if (this.currentView === 'map') {
            this.initializeMap();
        }
    }

    displayProjectsGrid() {
        const container = document.getElementById('gridContainer');
        container.innerHTML = '';

        if (this.allProjects.length === 0) {
            container.innerHTML = '<div class="col-span-full text-center py-12"><div class="text-gray-500 dark:text-gray-400"><i class="fas fa-folder-open text-4xl mb-4"></i><p class="text-lg">No projects found</p><p class="text-sm">Try adjusting your search criteria</p></div></div>';
            return;
        }

        this.allProjects.forEach(project => {
            const projectCard = this.createProjectCard(project);
            container.appendChild(projectCard);
        });
    }

    displayProjectsList() {
        const container = document.getElementById('listContainer');
        container.innerHTML = '';

        if (this.allProjects.length === 0) {
            container.innerHTML = '<div class="text-center py-12"><div class="text-gray-500 dark:text-gray-400"><i class="fas fa-folder-open text-4xl mb-4"></i><p class="text-lg">No projects found</p><p class="text-sm">Try adjusting your search criteria</p></div></div>';
            return;
        }

        this.allProjects.forEach(project => {
            const projectItem = this.createProjectListItem(project);
            container.appendChild(projectItem);
        });
    }

    initializeMap() {
        setTimeout(() => {
            if (!this.map) {
                // Initialize map centered on Migori County
                this.map = L.map('map').setView([-1.0636, 34.4733], 10);

                // Add OpenStreetMap tiles
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors'
                }).addTo(this.map);
            }

            // Clear existing markers
            this.markers.forEach(marker => this.map.removeLayer(marker));
            this.markers = [];

            // Add project markers
            this.allProjects.forEach(project => {
                if (project.location_coordinates) {
                    const coords = project.location_coordinates.split(',');
                    if (coords.length === 2) {
                        const lat = parseFloat(coords[0]);
                        const lng = parseFloat(coords[1]);

                        if (!isNaN(lat) && !isNaN(lng)) {
                            const marker = L.marker([lat, lng])
                                .bindPopup(this.createMapPopup(project))
                                .addTo(this.map);

                            this.markers.push(marker);

                            // Add click event to highlight project in sidebar
                            marker.on('click', () => {
                                this.highlightProjectInSidebar(project.id);
                            });
                        }
                    }
                }
            });

            // Update sidebar project list
            this.updateMapSidebar();

            // Fit map to show all markers if there are any
            if (this.markers.length > 0) {
                const group = new L.featureGroup(this.markers);
                this.map.fitBounds(group.getBounds().pad(0.1));
            }
        }, 100);
    }

    createProjectCard(project) {
        const card = document.createElement('div');
        card.className = 'bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-md transition-shadow';

        const statusClass = this.getStatusClass(project.status);
        const progressColor = this.getProgressColor(project.progress_percentage);

        card.innerHTML = `
            <div class="p-6">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                            <a href="${window.BASE_URL}project_details.php?id=${project.id}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                                ${project.project_name}
                            </a>
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">${project.description || 'No description'}</p>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${statusClass}">
                        ${project.status}
                    </span>
                </div>

                <div class="space-y-3">
                    <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                        <i class="fas fa-map-marker-alt w-4 h-4 mr-2"></i>
                        <span>${project.ward_name}, ${project.sub_county_name}</span>
                    </div>

                    <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                        <i class="fas fa-building w-4 h-4 mr-2"></i>
                        <span>${project.department_name}</span>
                    </div>

                    <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                        <i class="fas fa-money-bill-wave w-4 h-4 mr-2"></i>
                        <span>KES ${parseFloat(project.budget).toLocaleString()}</span>
                    </div>

                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Progress</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">${project.progress_percentage}%</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="h-2 rounded-full ${progressColor}" style="width: ${project.progress_percentage}%"></div>
                        </div>
                    </div>
                     <div class="flex items-center space-x-1">
                        ${this.generateStarRating(project.average_rating)}
                        <span class="text-sm text-gray-600 dark:text-gray-400">(${parseFloat(project.average_rating).toFixed(1)})</span>
                    </div>
                </div>
            </div>
        `;

        return card;
    }

    createProjectListItem(project) {
        const item = document.createElement('div');
        item.className = 'bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow';

        const statusClass = this.getStatusClass(project.status);
        const progressColor = this.getProgressColor(project.progress_percentage);

        item.innerHTML = `
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <div class="flex items-start justify-between mb-2">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            <a href="${window.BASE_URL}project_details.php?id=${project.id}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                                ${project.project_name}
                            </a>
                        </h3>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${statusClass}">
                            ${project.status}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm text-gray-600 dark:text-gray-400">
                        <div class="flex items-center">
                            <i class="fas fa-map-marker-alt w-4 h-4 mr-2"></i>
                            <span>${project.ward_name}, ${project.sub_county_name}</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-building w-4 h-4 mr-2"></i>
                            <span>${project.department_name}</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-money-bill-wave w-4 h-4 mr-2"></i>
                            <span>KES ${parseFloat(project.budget).toLocaleString()}</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-chart-line w-4 h-4 mr-2"></i>
                            <span>${project.progress_percentage}% Complete</span>
                        </div>
                    </div>

                    <div class="mt-3">
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="h-2 rounded-full ${progressColor}" style="width: ${project.progress_percentage}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        return item;
    }

    createMapPopup(project) {
        const statusClass = this.getStatusClass(project.status);
        return `
            <div class="p-2 max-w-xs">
                <h4 class="font-semibold text-gray-900 mb-2">
                    <a href="${window.BASE_URL}project_details.php?id=${project.id}" class="hover:text-blue-600 transition-colors">
                        ${project.project_name}
                    </a>
                </h4>
                <div class="space-y-1 text-sm text-gray-600">
                    <div><i class="fas fa-map-marker-alt mr-1"></i> ${project.ward_name}</div>
                    <div><i class="fas fa-building mr-1"></i> ${project.department_name}</div>
                    <div><i class="fas fa-money-bill-wave mr-1"></i> KES ${parseFloat(project.budget).toLocaleString()}</div>
                    <div class="flex items-center">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${statusClass}">
                            ${project.status}
                        </span>
                        <span class="ml-2">${project.progress_percentage}%</span>
                    </div>
                       <div class="flex items-center space-x-1">
                        ${this.generateStarRating(project.average_rating)}
                        <span class="text-sm text-gray-600 dark:text-gray-400">(${parseFloat(project.average_rating).toFixed(1)})</span>
                    </div>
                </div>
            </div>
        `;
    }

    updateMapSidebar() {
        const sidebarList = document.getElementById('mapProjectsList');
        sidebarList.innerHTML = '';

        this.allProjects.forEach(project => {
            const projectDiv = document.createElement('div');
            projectDiv.className = 'p-3 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition-colors';
            projectDiv.id = `sidebar-project-${project.id}`;

            const statusClass = this.getStatusClass(project.status);

            projectDiv.innerHTML = `
                <h4 class="font-medium text-gray-900 dark:text-white mb-1">
                    <a href="${window.BASE_URL}project_details.php?id=${project.id}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                        ${project.project_name}
                    </a>
                </h4>
                <div class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                    <div><i class="fas fa-map-marker-alt mr-1"></i> ${project.ward_name}</div>
                    <div class="flex items-center justify-between">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${statusClass}">
                            ${project.status}
                        </span>
                        <span>${project.progress_percentage}%</span>
                    </div>
                     <div class="flex items-center space-x-1">
                        ${this.generateStarRating(project.average_rating)}
                        <span class="text-sm text-gray-600 dark:text-gray-400">(${parseFloat(project.average_rating).toFixed(1)})</span>
                    </div>
                </div>
            `;

            // Click to center map on project
            projectDiv.addEventListener('click', (e) => {
                if (!e.target.closest('a')) {
                    this.centerMapOnProject(project);
                }
            });

            sidebarList.appendChild(projectDiv);
        });
    }

    centerMapOnProject(project) {
        if (project.location_coordinates && this.map) {
            const coords = project.location_coordinates.split(',');
            if (coords.length === 2) {
                const lat = parseFloat(coords[0]);
                const lng = parseFloat(coords[1]);

                if (!isNaN(lat) && !isNaN(lng)) {
                    this.map.setView([lat, lng], 13);
                    // Find and open the marker popup
                    this.markers.forEach(marker => {
                        const markerPos = marker.getLatLng();
                        if (Math.abs(markerPos.lat - lat) < 0.001 && Math.abs(markerPos.lng - lng) < 0.001) {
                            marker.openPopup();
                        }
                    });
                }
            }
        }
    }

    highlightProjectInSidebar(projectId) {
        // Remove existing highlights
        document.querySelectorAll('[id^="sidebar-project-"]').forEach(el => {
            el.classList.remove('bg-blue-50', 'dark:bg-blue-900', 'border-blue-300', 'dark:border-blue-600');
        });

        // Highlight selected project
        const projectEl = document.getElementById(`sidebar-project-${projectId}`);
        if (projectEl) {
            projectEl.classList.add('bg-blue-50', 'dark:bg-blue-900', 'border-blue-300', 'dark:border-blue-600');
            projectEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }

    getStatusClass(status) {
        const classes = {
            'planning': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
            'ongoing': 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
            'completed': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
            'suspended': 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
            'cancelled': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
        };
        return classes[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300';
    }

    getProgressColor(percentage) {
        if (percentage >= 80) return 'bg-green-500';
        if (percentage >= 60) return 'bg-blue-500';
        if (percentage >= 40) return 'bg-yellow-500';
        if (percentage >= 20) return 'bg-orange-500';
        return 'bg-red-500';
    }

     generateStarRating(rating) {
        const fullStars = Math.floor(rating);
        const halfStar = (rating - fullStars) >= 0.5;
        const emptyStars = 5 - fullStars - (halfStar ? 1 : 0);

        let stars = '';

        // Full stars
        for (let i = 0; i < fullStars; i++) {
            stars += '<i class="fas fa-star text-yellow-400"></i>';
        }

        // Half star
        if (halfStar) {
            stars += '<i class="fas fa-star-half-alt text-yellow-400"></i>';
        }

        // Empty stars
        for (let i = 0; i < emptyStars; i++) {
            stars += '<i class="far fa-star text-gray-300"></i>';
        }

        return `<div class="flex space-x-1">${stars}</div>`;
    }

    initializeFilters() {
        const wardFilter = document.getElementById('ward_filter');
        const statusFilter = document.getElementById('status_filter');

        if (wardFilter) {
            wardFilter.addEventListener('change', () => this.loadProjects());
        }

        if (statusFilter) {
            statusFilter.addEventListener('change', () => this.loadProjects());
        }
    }
}

// Global instances
let themeManager;
let projectManager;
let feedbackManager;
let mapManager;
let indexPageManager;

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    themeManager = new ThemeManager();
    projectManager = new ProjectManager();
    feedbackManager = new FeedbackManager();

    // Initialize mapManager only if Leaflet is defined
    if (typeof L !== 'undefined') {
        mapManager = new MapManager();
    }

    // Initialize index page manager if on index page
    if (document.getElementById('gridContainer') || document.getElementById('listContainer')) {
        indexPageManager = new IndexPageManager();
        indexPageManager.init();
    }

    // Define BASE_URL from PHP in global scope
    window.BASE_URL = window.BASE_URL || "http://localhost/homeprojectmanagement/";
});

// Global functions for HTML onclick handlers
function showProjectDetails(projectId) {
    projectManager.showProjectDetails(projectId);
}

function closeProjectDetails() {
    projectManager.closeProjectDetails();
}

function showFeedbackForm(projectId) {
    feedbackManager.showFeedbackForm(projectId);
}

function closeFeedbackForm() {
    feedbackManager.closeFeedbackForm();
}

function submitFeedback(event) {
    return feedbackManager.submitFeedback(event);
}

function exportPDF() {
    return ExportManager.exportPDF();
}

function exportCSV() {
    return ExportManager.exportCSV();
}

function showMap() {
    if (mapManager) {
        mapManager.showMap();
    }
}

function closeMap() {
    if (mapManager) {
        mapManager.closeMap();
    }
}

// Index page specific functions
function switchView(viewType) {
    if (indexPageManager) {
        indexPageManager.switchView(viewType);
    }
}

function applyFilters() {
    if (indexPageManager) {
        indexPageManager.loadProjects();
    }
}