// Main JavaScript file for County Project Tracking System
// Note: ThemeManager and other classes are defined in app.js

// Global instances (initialized in app.js)
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
            const response = await fetch(`api/projects.php?id=${projectId}`);
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
            const response = await fetch(`api/export_pdf.php?project_id=${projectId}`);
            
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
            const response = await fetch('api/feedback.php', {
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

// Export Functions
class ExportManager {
    static async exportPDF() {
        try {
            const urlParams = new URLSearchParams(window.location.search);
            const queryString = urlParams.toString();
            
            const response = await fetch(`api/export_pdf.php?${queryString}`);
            
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
            
            const response = await fetch(`api/export_csv.php?${queryString}`);
            
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
    
    // Initialize map if available
    if (typeof MapManager !== 'undefined') {
        mapManager = new MapManager();
    }
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
