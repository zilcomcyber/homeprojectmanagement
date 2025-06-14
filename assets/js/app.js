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
            if (!project.location_coordinates || typeof project.location_coordinates !== 'string') {
                console.warn(`No location coordinates for project ${project.id}`);
                return;
            }

            const coordParts = project.location_coordinates.split(',');
            if (coordParts.length !== 2) {
                console.warn(`Invalid coordinate format for project ${project.id}: ${project.location_coordinates}`);
                return;
            }

            const lat = parseFloat(coordParts[0].trim());
            const lng = parseFloat(coordParts[1].trim());

            if (isNaN(lat) || isNaN(lng)) {
                console.warn(`Invalid coordinates for project ${project.id}: ${project.location_coordinates} (lat: ${lat}, lng: ${lng})`);
                return;
            }

            // Validate coordinate ranges (basic sanity check)
            if (lat < -90 || lat > 90 || lng < -180 || lng > 180) {
                console.warn(`Coordinates out of range for project ${project.id}: lat=${lat}, lng=${lng}`);
                return;
            }

            const markerColor = this.getMarkerColor(project.status);

            const markerIcon = L.divIcon({
                className: 'custom-marker',
                html: `<div class="relative">
                         <i class="fas fa-map-marker-alt text-2xl ${markerColor} drop-shadow-lg"></i>
                       </div>`,
                iconSize: [24, 32],
                iconAnchor: [12, 32]
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
            planning: 'text-yellow-500',
            ongoing: 'text-blue-500',
            completed: 'text-green-500',
            suspended: 'text-orange-500',
            cancelled: 'text-red-500'
        };
        return colors[status] || 'text-gray-500';
    }

    createPopupContent(project) {
        const statusBadge = this.getStatusBadgeClass(project.status);

        return `
            <div class="p-3 min-w-0">
                <div class="flex items-start justify-between mb-2">
                    <h4 class="font-semibold text-gray-900 text-sm leading-tight pr-2">
                        ${this.escapeHtml(project.project_name)}
                    </h4>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${statusBadge} whitespace-nowrap">
                        ${project.status.charAt(0).toUpperCase() + project.status.slice(1)}
                    </span>
                </div>

                <div class="space-y-1 text-xs text-gray-600 mb-3">
                    <div>
                        <span class="font-medium">Department:</span> ${this.escapeHtml(project.department_name)}
                    </div>
                    <div>
                        <span class="font-medium">Location:</span> ${this.escapeHtml(project.ward_name)}, ${this.escapeHtml(project.sub_county_name)}
                    </div>
                    <div>
                        <span class="font-medium">Year:</span> ${project.project_year}
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

                <div class="mt-2 mb-3">
                    <div class="flex items-center space-x-1">
                        ${this.generateStarRating(project.average_rating)}
                        <span class="text-xs text-gray-600">${parseFloat(project.average_rating).toFixed(1)} (${project.total_ratings})</span>
                    </div>
                </div>

                <div class="mt-3">
                    <a href="project_details.php?id=${project.id}" class="text-blue-600 hover:text-blue-800 text-xs font-medium">
                        <i class="fas fa-eye mr-1"></i>View Details
                    </a>
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

// Feedback modal functions
function openFeedbackModal(projectId) {
    if (feedbackManager) {
        feedbackManager.showFeedbackForm(projectId);
    }
}

function closeFeedbackModal() {
    if (feedbackManager) {
        feedbackManager.closeFeedbackForm();
    }
}

// Rating modal functions
function openRatingModal(projectId) {
    document.getElementById('rating-project-id').value = projectId;
    document.getElementById('rating-modal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeRatingModal() {
    document.getElementById('rating-modal').classList.add('hidden');
    document.getElementById('rating-form').reset();
    document.body.style.overflow = 'auto';

    // Reset star rating
    const stars = document.querySelectorAll('.star-btn');
    stars.forEach(star => {
        star.innerHTML = '<i class="far fa-star"></i>';
        star.classList.remove('text-yellow-400');
        star.classList.add('text-gray-300');
    });
    document.getElementById('selected-rating').value = '';
}

// Global instances
let themeManager;
let projectManager;
let feedbackManager;
let mapManager;

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    themeManager = new ThemeManager();
    projectManager = new ProjectManager();
    feedbackManager = new FeedbackManager();

    // Initialize mapManager only if Leaflet is defined
    if (typeof L !== 'undefined') {
        mapManager = new MapManager();
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
    // Update button states
    document.querySelectorAll('.view-btn-modern').forEach(btn => {
        btn.classList.remove('active');
    });

    // Hide all containers
    const gridContainer = document.getElementById('gridContainer');
    const listContainer = document.getElementById('listContainer');
    const mapContainer = document.getElementById('mapContainer');

    if (gridContainer) gridContainer.classList.add('hidden');
    if (listContainer) listContainer.classList.add('hidden');
    if (mapContainer) mapContainer.classList.add('hidden');

    // Show selected view and set active button
    if (viewType === 'grid') {
        const gridViewBtn = document.getElementById('gridView');
        if (gridViewBtn) {
            gridViewBtn.classList.add('active');
        }
        if (gridContainer) {
            gridContainer.classList.remove('hidden');
            setTimeout(() => initGridMaps(), 200);
        }
    } else if (viewType === 'list') {
        const listViewBtn = document.getElementById('listView');
        if (listViewBtn) {
            listViewBtn.classList.add('active');
        }
        if (listContainer) {
            listContainer.classList.remove('hidden');
            setTimeout(() => renderListView(), 200);
        }
    } else if (viewType === 'map') {
        const mapViewBtn = document.getElementById('mapView');
        if (mapViewBtn) {
            mapViewBtn.classList.add('active');
        }
        if (mapContainer) {
            mapContainer.classList.remove('hidden');
            setTimeout(() => renderMapView(), 200);
        }
    }
}

// Initialize maps for grid view cards
// View Management
let currentView = 'grid';
let allProjects = window.projectsData || [];

function switchView(view) {
    currentView = view;

    // Update active button
    document.querySelectorAll('.view-btn-modern').forEach(btn => {
        btn.classList.remove('active');
    });
    document.getElementById(view + 'View').classList.add('active');

    // Hide all containers
    document.getElementById('gridContainer').classList.add('hidden');
    document.getElementById('listContainer').classList.add('hidden');
    document.getElementById('mapContainer').classList.add('hidden');

    // Show selected container
    switch(view) {
        case 'grid':
            document.getElementById('gridContainer').classList.remove('hidden');
            setTimeout(() => initGridMaps(), 100);
            break;
        case 'list':
            document.getElementById('listContainer').classList.remove('hidden');
            renderListView();
            break;
        case 'map':
            document.getElementById('mapContainer').classList.remove('hidden');
            setTimeout(() => renderMapView(), 100);
            break;
    }
}

function renderListView() {
    const container = document.getElementById('listContainer');
    if (!allProjects || !allProjects.length) {
        container.innerHTML = '<div class="glass-card text-center py-16"><p class="text-gray-500 dark:text-gray-400">No projects available</p></div>';
        return;
    }

    // Categorize projects by status
    const ongoingProjects = allProjects.filter(p => p.status === 'ongoing');
    const completedProjects = allProjects.filter(p => p.status === 'completed');
    const planningProjects = allProjects.filter(p => p.status === 'planning');

    function renderProjectCategory(projects, title, badgeClass) {
        if (!projects.length) return '';
        
        return `
            <div class="mb-10">
                <div class="category-header-modern mb-6">
                    <div class="category-title-modern">
                        <span class="category-badge-modern ${badgeClass}">${title}</span>
                        ${title} Projects
                    </div>
                    <span class="category-count-modern">
                        ${projects.length} projects
                    </span>
                </div>
                <div class="space-y-4">
                    ${projects.map(project => `
                        <div class="glass-card p-6 hover:shadow-xl transition-all duration-300">
                            <div class="flex flex-col lg:flex-row gap-6">
                                <!-- Map Preview -->
                                <div class="w-full lg:w-48 h-32 bg-gray-200 rounded-lg overflow-hidden">
                                    ${project.location_coordinates ? 
                                        `<div id="list-map-preview-${project.id}" class="w-full h-full"></div>` :
                                        `<div class="w-full h-full flex items-center justify-center text-gray-500">
                                            <i class="fas fa-map-marker-alt text-2xl"></i>
                                            <p class="text-sm mt-2">No location data</p>
                                        </div>`
                                    }
                                </div>

                                <!-- Project Info -->
                                <div class="flex-1">
                                    <div class="flex items-start justify-between mb-4">
                                        <div>
                                            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                                                ${project.project_name}
                                            </h3>
                                            <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                                                <span><i class="fas fa-building mr-1"></i>${project.department_name}</span>
                                                <span><i class="fas fa-map-marker-alt mr-1"></i>${project.ward_name}, ${project.sub_county_name}</span>
                                                <span><i class="fas fa-calendar mr-1"></i>${project.project_year}</span>
                                            </div>
                                        </div>
                                        <span class="status-badge-modern status-${project.status}">
                                            ${project.status.charAt(0).toUpperCase() + project.status.slice(1)}
                                        </span>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex gap-3">
                                        <a href="project_details.php?id=${project.id}" 
                                           class="btn-modern btn-primary-modern">
                                            <i class="fas fa-eye"></i>
                                            View Details
                                        </a>
                                        <button onclick="openFeedbackModal(${project.id})" 
                                                class="btn-modern btn-secondary-modern">
                                            <i class="fas fa-comment"></i>
                                            Feedback
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }

    const listHTML = `
        ${renderProjectCategory(ongoingProjects, 'Ongoing', 'status-ongoing')}
        ${renderProjectCategory(completedProjects, 'Completed', 'status-completed')}
        ${renderProjectCategory(planningProjects, 'Planning', 'status-planning')}
    `;

    container.innerHTML = listHTML;
    
    // Initialize maps for list view after rendering
    setTimeout(() => initListMaps(), 100);
}

function renderMapView() {
    if (!allProjects || !allProjects.length) {
        const projectsList = document.getElementById('mapProjectsList');
        if (projectsList) {
            projectsList.innerHTML = '<div class="text-center text-gray-500 py-8"><p>No projects available</p></div>';
        }
        return;
    }

    // Generate project list HTML
    const projectListHTML = allProjects.map(project => `
        <div class="map-project-item p-3 border border-gray-200 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-blue-50 dark:hover:bg-gray-700 transition-colors" 
             data-project-id="${project.id}" onclick="highlightProjectOnMap(${project.id})">
            <h4 class="font-medium text-sm mb-1 text-gray-900 dark:text-white">${project.project_name}</h4>
            <p class="text-xs text-gray-600 dark:text-gray-400">${project.ward_name}, ${project.sub_county_name}</p>
            <div class="flex items-center justify-between mt-2">
                <span class="text-xs px-2 py-1 rounded status-badge-modern status-${project.status}">
                    ${project.status.charAt(0).toUpperCase() + project.status.slice(1)}
                </span>
                <span class="text-xs text-gray-600 dark:text-gray-400">${project.project_year}</span>
            </div>
        </div>
    `).join('');

    // Update the project list
    const projectsList = document.getElementById('mapProjectsList');
    if (projectsList) {
        projectsList.innerHTML = projectListHTML;
    }

    // Initialize the main map view
    setTimeout(() => initMainMapView(), 200);
}

function initGridMaps() {
    if (typeof L === 'undefined') {
        console.warn('Leaflet not loaded');
        return;
    }

    const mapElements = document.querySelectorAll('[id^="map-preview-"]');
    
    mapElements.forEach(mapEl => {
        // Skip if already initialized
        if (mapEl._leaflet_id) {
            return;
        }
        
        const projectId = mapEl.id.replace('map-preview-', '');
        const project = allProjects.find(p => p.id == projectId);

        if (project && project.location_coordinates) {
            try {
                const coords = project.location_coordinates.split(',');
                if (coords.length === 2) {
                    const lat = parseFloat(coords[0].trim());
                    const lng = parseFloat(coords[1].trim());

                    if (!isNaN(lat) && !isNaN(lng)) {
                        // Clear any existing content
                        mapEl.innerHTML = '';
                        
                        const map = L.map(mapEl, {
                            zoomControl: false,
                            dragging: false,
                            touchZoom: false,
                            doubleClickZoom: false,
                            scrollWheelZoom: false,
                            boxZoom: false,
                            keyboard: false,
                            attributionControl: false
                        }).setView([lat, lng], 13);

                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: ''
                        }).addTo(map);

                        // Use consistent custom marker
                        createCustomMarker(lat, lng, project).addTo(map);
                    }
                }
            } catch (error) {
                console.warn('Error initializing map for project:', projectId, error);
            }
        }
    });
}

function initListMaps() {
    if (typeof L === 'undefined') {
        console.warn('Leaflet not loaded');
        return;
    }
    
    const projects = allProjects || window.projectsData || [];
    projects.forEach(project => {
        if (project.location_coordinates) {
            try {
                const coords = project.location_coordinates.split(',');
                const lat = parseFloat(coords[0].trim());
                const lng = parseFloat(coords[1].trim());

                if (!isNaN(lat) && !isNaN(lng)) {
                    const listMapElement = document.getElementById(`list-map-preview-${project.id}`);
                    if (listMapElement && !listMapElement._leaflet_id) {
                        // Clear any existing content
                        listMapElement.innerHTML = '';
                        
                        const listMap = L.map(listMapElement, {
                            zoomControl: false,
                            scrollWheelZoom: false,
                            doubleClickZoom: false,
                            dragging: false,
                            attributionControl: false
                        }).setView([lat, lng], 14);

                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(listMap);

                        // Use consistent custom marker
                        createCustomMarker(lat, lng, project).addTo(listMap);
                    }
                }
            } catch (e) {
                console.warn('Invalid coordinates for project:', project.id, e);
            }
        }
    });
}

let mainMapView = null;
let mapViewMarkers = [];

function initMainMapView() {
    if (typeof L === 'undefined') {
        console.error('Leaflet is not loaded');
        return;
    }

    const mapElement = document.getElementById('mainMapView');
    if (!mapElement) {
        console.error('Map element not found');
        return;
    }

    if (mainMapView) {
        mainMapView.remove();
        mapViewMarkers = [];
    }

    try {
        mainMapView = L.map('mainMapView').setView([-0.7893, 34.7608], 10); // Migori County center

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(mainMapView);

        // Add project markers with consistent styling
        if (allProjects && allProjects.length > 0) {
            allProjects.forEach(project => {
                if (project.location_coordinates) {
                    try {
                        const coords = project.location_coordinates.split(',');
                        const lat = parseFloat(coords[0]);
                        const lng = parseFloat(coords[1]);

                        if (!isNaN(lat) && !isNaN(lng)) {
                            // Use the same custom marker style as project details
                            const marker = createCustomMarker(lat, lng, project).addTo(mainMapView);
                            marker.projectId = project.id;

                            marker.bindPopup(`
                                <div class="p-3">
                                    <h4 class="font-bold text-sm mb-1">${project.project_name}</h4>
                                    <p class="text-xs text-gray-600 mb-2">${project.ward_name}, ${project.sub_county_name}</p>
                                    <p class="text-xs text-gray-500 mb-2">Department: ${project.department_name}</p>
                                    <div class="flex gap-2">
                                        <a href="project_details.php?id=${project.id}" class="text-blue-600 text-xs font-medium">
                                            <i class="fas fa-eye mr-1"></i>View Details
                                        </a>
                                        <button onclick="openFeedbackModal(${project.id})" class="text-green-600 text-xs font-medium">
                                            <i class="fas fa-comment mr-1"></i>Feedback
                                        </button>
                                    </div>
                                </div>
                            `);

                            marker.on('click', () => highlightProjectInSidebar(project.id));
                            mapViewMarkers.push(marker);
                        }
                    } catch (e) {
                        console.warn('Invalid coordinates for project:', project.id);
                    }
                }
            });

            // Fit bounds if markers exist
            if (mapViewMarkers.length > 0) {
                const group = new L.featureGroup(mapViewMarkers);
                mainMapView.fitBounds(group.getBounds().pad(0.1));
            }
        }
    } catch (error) {
        console.error('Error initializing main map view:', error);
    }
}

function getMarkerColor(status) {
    const colors = {
        planning: 'bg-yellow-500',
        ongoing: 'bg-blue-500',
        completed: 'bg-green-500',
        suspended: 'bg-orange-500',
        cancelled: 'bg-red-500'
    };
    return colors[status] || 'bg-gray-500';
}

function highlightProjectOnMap(projectId) {
    const marker = mapViewMarkers.find(m => m.projectId === projectId);
    if (marker && mainMapView) {
        mainMapView.setView(marker.getLatLng(), 15);
        marker.openPopup();
    }
}

function highlightProjectInSidebar(projectId) {
    // Remove previous highlights
    document.querySelectorAll('.map-project-item').forEach(item => {
        item.classList.remove('bg-blue-50', 'border-blue-300');
        item.classList.add('border-gray-200');
    });

    // Highlight the selected project
    const projectItem = document.querySelector(`[data-project-id="${projectId}"]`);
    if (projectItem) {
        projectItem.classList.add('bg-blue-50', 'border-blue-300');
        projectItem.classList.remove('border-gray-200');
        projectItem.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    if (typeof window.projectsData !== 'undefined') {
        allProjects = window.projectsData;
        setTimeout(() => initGridMaps(), 100);
    }
});w
function initListMaps() {
    const projects = window.projectsData || [];
    projects.forEach(project => {
        if (project.location_coordinates) {
            try {
                const coords = project.location_coordinates.split(',');
                const lat = parseFloat(coords[0]);
                const lng = parseFloat(coords[1]);

                if (!isNaN(lat) && !isNaN(lng)) {
                    const listMapElement = document.getElementById(`list-map-preview-${project.id}`);
                    if (listMapElement && !listMapElement.hasChildNodes()) {
                        const listMap = L.map(listMapElement, {
                            zoomControl: false,
                            scrollWheelZoom: false,
                            doubleClickZoom: false,
                            dragging: false
                        }).setView([lat, lng], 14);

                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(listMap);
                        L.marker([lat, lng]).addTo(listMap);
                    }
                }
            } catch (e) {
                console.warn('Invalid coordinates for project:', project.id);
            }
        }
    });
}

// Initialize main map for map view
function initMainMap() {
    const mapContainer = document.getElementById('map');
    if (!mapContainer) return;

    const projects = window.projectsData || [];
    const projectsWithCoords = projects.filter(p => p.location_coordinates);

    if (projectsWithCoords.length === 0) return;

    // Clear existing map
    mapContainer.innerHTML = '';

    const map = L.map('map').setView([-1.0635, 34.4669], 10);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

    projectsWithCoords.forEach(project => {
        try {
            const coords = project.location_coordinates.split(',');
            const lat = parseFloat(coords[0]);
            const lng = parseFloat(coords[1]);

            if (!isNaN(lat) && !isNaN(lng)) {
                // Create marker with color based on status
                const markerColor = getMarkerColor(project.status);
                const markerIcon = L.divIcon({
                    className: 'custom-marker',
                    html: `<div class="relative">
                             <i class="fas fa-map-marker-alt text-2xl ${markerColor} drop-shadow-lg"></i>
                           </div>`,
                    iconSize: [24, 32],
                    iconAnchor: [12, 32]
                });

                const marker = L.marker([lat, lng], { icon: markerIcon }).addTo(map);

                // Add click event to highlight project in sidebar
                marker.on('click', function() {
                    highlightProjectInSidebar(project.id);
                });

                marker.bindPopup(`
                    <div class="p-3">
                        <h3 class="font-semibold text-sm mb-1">${project.project_name}</h3>
                        <p class="text-xs text-gray-600 mb-2">${project.ward_name}, ${project.sub_county_name}</p>
                        <p class="text-xs text-gray-500 mb-2">Department: ${project.department_name}</p>
                        <div class="mt-2">
                            <a href="project_details.php?id=${project.id}" class="text-blue-600 hover:text-blue-800 text-xs font-medium">
                                <i class="fas fa-eye mr-1"></i>View Details
                            </a>
                        </div>
                    </div>
                `);
            }
        } catch (e) {
            console.warn('Invalid coordinates for project:', project.id);
        }
    });
}

function getMarkerColor(status) {
    const colors = {
        planning: 'text-yellow-500',
        ongoing: 'text-blue-500',
        completed: 'text-green-500',
        suspended: 'text-orange-500',
        cancelled: 'text-red-500'
    };
    return colors[status] || 'text-gray-500';
}

function highlightProjectInSidebar(projectId) {
    // Remove previous highlights
    document.querySelectorAll('.map-project-item').forEach(item => {
        item.classList.remove('bg-blue-50', 'border-blue-300');
        item.classList.add('border-gray-200');
    });

    // Highlight the selected project
    const projectItem = document.querySelector(`[data-project-id="${projectId}"]`);
    if (projectItem) {
        projectItem.classList.add('bg-blue-50', 'border-blue-300');
        projectItem.classList.remove('border-gray-200');
        projectItem.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

function applyFilters() {
    if (indexPageManager) {
        indexPageManager.loadProjects();
    }
}

// Mobile-optimized action buttons for project cards
function showMobileActions(projectId) {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-end justify-center p-4';
    modal.innerHTML = `
        <div class="bg-white dark:bg-gray-800 rounded-t-xl w-full max-w-sm p-6 space-y-4 animate-slide-up">
            <div class="text-center">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Project Actions</h3>
            </div>
            <div class="space-y-3">
                <a href="project_details.php?id=${projectId}" 
                   class="w-full flex items-center justify-center px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-eye mr-2"></i>
                    View Details
                </a>
                <button onclick="openFeedbackModal(${projectId}); this.closest('.fixed').remove();" 
                        class="w-full flex items-center justify-center px-4 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                    <i class="fas fa-comment mr-2"></i>
                    Give Feedback
                </button>
                <button onclick="this.closest('.fixed').remove();" 
                        class="w-full flex items-center justify-center px-4 py-3 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors">
                    Cancel
                </button>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    // Close modal when clicking outside
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.remove();
        }
    });
}

// Initialize the page
document.addEventListener('DOMContentLoaded', function() {
    // Initialize filter functionality only
    const departmentFilter = document.getElementById('departmentFilter');
    const statusFilter = document.getElementById('statusFilter');
    const yearFilter = document.getElementById('yearFilter');

    if (departmentFilter) {
        departmentFilter.addEventListener('change', applyFilters);
    }
    if (statusFilter) {
        statusFilter.addEventListener('change', applyFilters);
    }
    if (yearFilter) {
        yearFilter.addEventListener('change', applyFilters);
    }

    // Mobile menu toggle
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');

    if (mobileMenuButton && mobileMenu) {
        mobileMenuButton.addEventListener('click', function() {
            mobileMenu.classList.toggle('hidden');
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            if (!mobileMenuButton.contains(event.target) && !mobileMenu.contains(event.target)) {
                mobileMenu.classList.add('hidden');
            }
        });
    }

    // Scroll to top functionality
    const scrollToTopBtn = document.getElementById('scrollToTop');
    if (scrollToTopBtn) {
        // Show/hide scroll to top button
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                scrollToTopBtn.classList.add('opacity-100', 'visible');
                scrollToTopBtn.classList.remove('opacity-0', 'invisible', 'translate-y-4');
            } else {
                scrollToTopBtn.classList.add('opacity-0', 'invisible', 'translate-y-4');
                scrollToTopBtn.classList.remove('opacity-100', 'visible');
            }
        });

        // Scroll to top when clicked
        scrollToTopBtn.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }

    // Feedback form submission
    const feedbackForm = document.getElementById('feedbackForm');
    if (feedbackForm) {
        feedbackForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitFeedback(e);
        });
    }

    // Initialize view switching if on index page
    if (typeof window.projectsData !== 'undefined') {
        allProjects = window.projectsData;
        
        // Ensure grid view is shown by default and other views are hidden
        const gridContainer = document.getElementById('gridContainer');
        const listContainer = document.getElementById('listContainer');
        const mapContainer = document.getElementById('mapContainer');

        // Make sure grid view is visible by default
        if (gridContainer) {
            gridContainer.classList.remove('hidden');
        }
        if (listContainer) listContainer.classList.add('hidden');
        if (mapContainer) mapContainer.classList.add('hidden');

        // Set grid view button as active by default
        const gridViewBtn = document.getElementById('gridView');
        const listViewBtn = document.getElementById('listView');
        const mapViewBtn = document.getElementById('mapView');

        if (gridViewBtn) {
            gridViewBtn.classList.add('active');
        }
        if (listViewBtn) {
            listViewBtn.classList.remove('active');
        }
        if (mapViewBtn) {
            mapViewBtn.classList.remove('active');
        }

        // Initialize grid maps after a delay to ensure DOM is ready
        setTimeout(() => {
            if (typeof initGridMaps === 'function') {
                initGridMaps();
            }
        }, 300);
    }
});

// Apply filters function
function applyFilters() {
    const departmentId = document.getElementById('departmentFilter')?.value || '';
    const status = document.getElementById('statusFilter')?.value || '';
    const year = document.getElementById('yearFilter')?.value || '';

    // Build query string
    const params = new URLSearchParams(window.location.search);

    if (departmentId) {
        params.set('department', departmentId);
    } else {
        params.delete('department');
    }

    if (status) {
        params.set('status', status);
    } else {
        params.delete('status');
    }

    if (year) {
        params.set('year', year);
    } else {
        params.delete('year');
    }

    // Redirect with new filters
    window.location.href = window.location.pathname + '?' + params.toString();
}

function createCustomMarker(lat, lng, project) {
    const markerHtml = `
        <div class="custom-marker" style="
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            border-radius: 50% 50% 50% 0;
            transform: rotate(-45deg);
            border: 3px solid white;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        ">
            <i class="fas fa-map-marker-alt" style="
                transform: rotate(45deg);
                color: white;
                font-size: 16px;
            "></i>
        </div>
    `;

    const customIcon = L.divIcon({
        html: markerHtml,
        className: 'custom-div-icon',
        iconSize: [40, 40],
        iconAnchor: [20, 40],
        popupAnchor: [0, -40]
    });

    return L.marker([lat, lng], { icon: customIcon });
}

function initGridMaps() {
    if (typeof L === 'undefined') return;

    const mapElements = document.querySelectorAll('[id^="map-preview-"]');
    mapElements.forEach(mapEl => {
        if (mapEl._leaflet_id) return; // Skip if already initialized
        
        const projectId = mapEl.id.replace('map-preview-', '');
        const project = allProjects.find(p => p.id == projectId);

        if (project && project.location_coordinates) {
            const coords = project.location_coordinates.split(',');
            if (coords.length === 2) {
                const lat = parseFloat(coords[0]);
                const lng = parseFloat(coords[1]);

                if (!isNaN(lat) && !isNaN(lng)) {
                    const map = L.map(mapEl, {
                        zoomControl: false,
                        dragging: false,
                        touchZoom: false,
                        doubleClickZoom: false,
                        scrollWheelZoom: false,
                        boxZoom: false,
                        keyboard: false
                    }).setView([lat, lng], 13);

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: ''
                    }).addTo(map);

                    // Use consistent custom marker
                    createCustomMarker(lat, lng, project).addTo(map);
                }
            }
        }
    });
}

function initListMaps() {
    if (typeof L === 'undefined') return;
    
    const projects = window.projectsData || [];
    projects.forEach(project => {
        if (project.location_coordinates) {
            try {
                const coords = project.location_coordinates.split(',');
                const lat = parseFloat(coords[0]);
                const lng = parseFloat(coords[1]);

                if (!isNaN(lat) && !isNaN(lng)) {
                    const listMapElement = document.getElementById(`list-map-preview-${project.id}`);
                    if (listMapElement && !listMapElement._leaflet_id) {
                        const listMap = L.map(listMapElement, {
                            zoomControl: false,
                            scrollWheelZoom: false,
                            doubleClickZoom: false,
                            dragging: false
                        }).setView([lat, lng], 14);

                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(listMap);

                        // Use consistent custom marker
                        createCustomMarker(lat, lng, project).addTo(listMap);
                    }
                }
            } catch (e) {
                console.warn('Invalid coordinates for project:', project.id);
            }
        }
    });
}
// Make sure to pass your projects data when initializing
const success = initMainMapView(yourProjectsArray);
if (!success) {
    console.error('Failed to initialize map');
}
