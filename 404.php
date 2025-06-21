<?php
// Check if this is a project-specific 404 (when project doesn't exist)
$is_project_404 = isset($_GET['type']) && $_GET['type'] === 'project';
$project_id = isset($_GET['id']) ? intval($_GET['id']) : null;
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_project_404 ? 'Project Not Found' : 'Page Not Found'; ?> - Migori County Projects</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/app.css">
    <style>
        body {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            min-height: 100vh;
        }
        
        .error-container {
            background: linear-gradient(135deg, 
                rgba(34, 197, 94, 0.08) 0%, 
                rgba(251, 191, 36, 0.06) 50%, 
                rgba(34, 197, 94, 0.05) 100%);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(34, 197, 94, 0.2);
            border-radius: 20px;
            box-shadow: 
                0 25px 50px rgba(0, 0, 0, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }
        
        .error-number {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 8rem;
            font-weight: 900;
            line-height: 1;
            margin-bottom: 1rem;
        }
        
        .error-icon {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(220, 38, 38, 0.15));
            border: 2px solid rgba(239, 68, 68, 0.2);
            backdrop-filter: blur(10px);
            color: #dc2626;
        }
        
        .btn-home {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        
        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4);
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
        }
        
        .btn-secondary {
            background: rgba(107, 114, 128, 0.1);
            color: #374151;
            border: 1px solid rgba(107, 114, 128, 0.2);
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            background: rgba(107, 114, 128, 0.15);
            transform: translateY(-1px);
        }
        
        .animated-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: linear-gradient(135deg,
                rgba(250, 245, 255, 1) 0%,
                rgba(243, 232, 255, 1) 50%,
                rgba(233, 213, 255, 0.3) 100%);
        }
        
        .animated-bg::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image:
                radial-gradient(circle at 20% 80%, rgba(147, 51, 234, 0.03) 0%, transparent 40%),
                radial-gradient(circle at 80% 20%, rgba(168, 85, 247, 0.02) 0%, transparent 40%),
                radial-gradient(circle at 40% 40%, rgba(139, 92, 246, 0.02) 0%, transparent 40%);
            animation: float 30s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            33% { transform: translateY(-20px) rotate(1deg); }
            66% { transform: translateY(10px) rotate(-1deg); }
        }
    </style>
</head>
<body>
    <div class="animated-bg"></div>
    
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="error-container max-w-2xl w-full text-center p-8 md:p-12">
            
            <!-- Error Icon -->
            <div class="error-icon w-20 h-20 mx-auto rounded-full flex items-center justify-center mb-6">
                <i class="fas fa-exclamation-triangle text-3xl"></i>
            </div>
            
            <!-- Error Number -->
            <div class="error-number">404</div>
            
            <!-- Error Message -->
            <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                <?php if ($is_project_404): ?>
                    Project Not Found
                <?php else: ?>
                    Page Not Found
                <?php endif; ?>
            </h1>
            
            <p class="text-lg text-gray-600 mb-8 max-w-md mx-auto leading-relaxed">
                <?php if ($is_project_404): ?>
                    The project you're looking for doesn't exist or may have been removed from our system.
                    <?php if ($project_id): ?>
                        <br><small class="text-sm text-gray-500">Project ID: <?php echo $project_id; ?></small>
                    <?php endif; ?>
                <?php else: ?>
                    The page you're looking for doesn't exist. It might have been moved, deleted, or you entered the wrong URL.
                <?php endif; ?>
            </p>
            
            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                <a href="index.php" class="btn-home">
                    <i class="fas fa-home"></i>
                    Back to Home
                </a>
                
                <a href="index.php" class="btn-secondary">
                    <i class="fas fa-search"></i>
                    Browse Projects
                </a>
            </div>
            
            <!-- Quick Links -->
            <div class="mt-12 pt-8 border-t border-gray-200">
                <p class="text-sm text-gray-500 mb-4">Looking for something specific?</p>
                <div class="flex flex-wrap justify-center gap-4 text-sm">
                    <a href="index.php?status=ongoing" class="text-blue-600 hover:text-blue-500 transition-colors">
                        <i class="fas fa-tools mr-1"></i>
                        Ongoing Projects
                    </a>
                    <a href="index.php?status=completed" class="text-green-600 hover:text-green-500 transition-colors">
                        <i class="fas fa-check-circle mr-1"></i>
                        Completed Projects
                    </a>
                    <a href="index.php" class="text-purple-600 hover:text-purple-500 transition-colors">
                        <i class="fas fa-map-marked-alt mr-1"></i>
                        Project Map
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Add some interactive elements
        document.addEventListener('DOMContentLoaded', function() {
            // Animate error number on load
            const errorNumber = document.querySelector('.error-number');
            if (errorNumber) {
                errorNumber.style.opacity = '0';
                errorNumber.style.transform = 'scale(0.8)';
                
                setTimeout(() => {
                    errorNumber.style.transition = 'all 0.8s cubic-bezier(0.4, 0, 0.2, 1)';
                    errorNumber.style.opacity = '1';
                    errorNumber.style.transform = 'scale(1)';
                }, 200);
            }
        });
    </script>
</body>
</html>
