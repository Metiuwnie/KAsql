// Theme Toggle Script - Robust Version
(function() {
    'use strict';
    
    console.log('🎨 Theme script loaded');
    
    // Initialize theme immediately (before DOM ready)
    function initThemeEarly() {
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme) {
            document.documentElement.setAttribute('data-theme', savedTheme);
            console.log('✅ Theme restored from localStorage:', savedTheme);
        } else {
            // Default to light mode
            const defaultTheme = 'light';
            document.documentElement.setAttribute('data-theme', defaultTheme);
            localStorage.setItem('theme', defaultTheme);
            console.log('✅ Default theme set:', defaultTheme);
        }
    }
    
    // Run immediately
    initThemeEarly();
    
    // Main initialization
    function initTheme() {
        const themeSwitch = document.getElementById('theme-switch');
        const themeSwitchSidebar = document.getElementById('theme-switch-sidebar');
        
        console.log('🔍 Theme switch navbar:', themeSwitch);
        console.log('🔍 Theme switch sidebar:', themeSwitchSidebar);
        
        // Get current theme
        let currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
        console.log('📌 Current theme:', currentTheme);
        
        // Update button text
        updateButtonText(currentTheme);
        
        // Add event listeners with multiple methods
        if (themeSwitch) {
            // Remove any existing listeners
            const newThemeSwitch = themeSwitch.cloneNode(true);
            themeSwitch.parentNode.replaceChild(newThemeSwitch, themeSwitch);
            
            newThemeSwitch.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('🖱️ Navbar theme button clicked');
                toggleTheme();
            });
            
            console.log('✅ Navbar button listener attached');
        }
        
        if (themeSwitchSidebar) {
            // Remove any existing listeners
            const newThemeSwitchSidebar = themeSwitchSidebar.cloneNode(true);
            themeSwitchSidebar.parentNode.replaceChild(newThemeSwitchSidebar, themeSwitchSidebar);
            
            newThemeSwitchSidebar.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('🖱️ Sidebar theme button clicked');
                toggleTheme();
            });
            
            console.log('✅ Sidebar button listener attached');
        }
        
        // Global toggle function
        window.toggleTheme = toggleTheme;
        
        function toggleTheme() {
            let currentTheme = document.documentElement.getAttribute('data-theme');
            let newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            console.log('🔄 Toggling from', currentTheme, 'to', newTheme);
            
            // Apply new theme
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateButtonText(newTheme);
            
            console.log('✅ Theme changed to:', newTheme);
            
            // Dispatch custom event
            window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme: newTheme } }));
        }
        
        function updateButtonText(theme) {
            console.log('🔄 Updating button text for theme:', theme);
            
            // Update navbar theme switch
            const navButton = document.getElementById('theme-switch');
            if (navButton) {
                if (theme === 'dark') {
                    navButton.innerHTML = '☀️ Light Mode';
                    navButton.setAttribute('title', 'Switch to Light Mode');
                } else {
                    navButton.innerHTML = '🌙 Dark Mode';
                    navButton.setAttribute('title', 'Switch to Dark Mode');
                }
                console.log('✅ Navbar button updated');
            }
            
            // Update sidebar theme switch
            const sidebarButton = document.getElementById('theme-switch-sidebar');
            if (sidebarButton) {
                const darkIcon = sidebarButton.querySelector('.theme-icon-dark');
                const lightIcon = sidebarButton.querySelector('.theme-icon-light');
                const textSpan = sidebarButton.querySelector('span');
                
                if (theme === 'dark') {
                    if (darkIcon) darkIcon.style.display = 'none';
                    if (lightIcon) lightIcon.style.display = 'block';
                    if (textSpan) textSpan.textContent = 'Light Mode';
                } else {
                    if (darkIcon) darkIcon.style.display = 'block';
                    if (lightIcon) lightIcon.style.display = 'none';
                    if (textSpan) textSpan.textContent = 'Dark Mode';
                }
                console.log('✅ Sidebar button updated');
            }
        }
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTheme);
    } else {
        initTheme();
    }
    
    // Re-initialize after a short delay (fallback)
    setTimeout(initTheme, 500);
    
    // Add smooth scroll behavior
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    });
    
    // Debug helper
    window.debugTheme = function() {
        console.log('=== THEME DEBUG ===');
        console.log('Current theme:', document.documentElement.getAttribute('data-theme'));
        console.log('LocalStorage:', localStorage.getItem('theme'));
        console.log('Navbar button:', document.getElementById('theme-switch'));
        console.log('Sidebar button:', document.getElementById('theme-switch-sidebar'));
        console.log('==================');
    };
    
    console.log('✅ Theme script initialized. Type debugTheme() in console for debug info.');
})();
