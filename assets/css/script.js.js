// assets/js/script.js
// No animations - pure functionality

document.addEventListener('DOMContentLoaded', function() {
    // Mobile sidebar toggle
    const mobileToggle = document.getElementById('mobileToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    if (mobileToggle) {
        mobileToggle.addEventListener('click', function(e) {
            e.preventDefault();
            toggleMobileSidebar();
        });
    }
    
    if (overlay) {
        overlay.addEventListener('click', function() {
            closeMobileSidebar();
        });
    }
    
    // Close sidebar on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeMobileSidebar();
        }
    });
    
    // Auto close sidebar on resize to desktop
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            closeMobileSidebar();
        }
    });
});

// Toggle mobile sidebar
function toggleMobileSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    if (sidebar) {
        sidebar.classList.toggle('show');
    }
    
    if (overlay) {
        overlay.classList.toggle('show');
    }
    
    // Prevent body scroll
    document.body.style.overflow = sidebar.classList.contains('show') ? 'hidden' : '';
}

// Close mobile sidebar
function closeMobileSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    if (sidebar) {
        sidebar.classList.remove('show');
    }
    
    if (overlay) {
        overlay.classList.remove('show');
    }
    
    document.body.style.overflow = '';
}

// Toggle submenu
function toggleSubmenu(element, event) {
    if (event) {
        event.preventDefault();
    }
    
    const parent = element.closest('.has-submenu');
    if (!parent) return;
    
    // Close other open submenus (optional)
    // const allSubmenus = document.querySelectorAll('.has-submenu.open');
    // allSubmenus.forEach(function(item) {
    //     if (item !== parent) {
    //         item.classList.remove('open');
    //         const sub = item.querySelector('.submenu');
    //         if (sub) sub.style.display = 'none';
    //     }
    // });
    
    parent.classList.toggle('open');
    
    const submenu = parent.querySelector('.submenu');
    if (submenu) {
        submenu.style.display = submenu.style.display === 'block' ? 'none' : 'block';
    }
}

// Confirm delete
function confirmDelete(message) {
    return confirm(message || 'Apakah Anda yakin ingin menghapus data ini?');
}

// Toast notification (simple)
function showToast(message, type) {
    const toast = document.createElement('div');
    toast.className = `toast-notification toast-${type || 'info'}`;
    toast.textContent = message;
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 12px 24px;
        background: ${type === 'success' ? '#10B981' : type === 'error' ? '#EF4444' : type === 'warning' ? '#F59E0B' : '#3B82F6'};
        color: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 9999;
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        max-width: 400px;
        opacity: 1;
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(function() {
        toast.style.opacity = '0';
        setTimeout(function() {
            toast.remove();
        }, 300);
    }, 3000);
}

// Format number
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

// Format date to Indonesian
function formatDateIndonesian(dateStr) {
    if (!dateStr) return '-';
    const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
                    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    const d = new Date(dateStr);
    return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
}