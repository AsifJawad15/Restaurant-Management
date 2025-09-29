/**
 * Restaurant Management System - Admin JavaScript
 * ASIF - Backend & Database Developer
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Sidebar toggle for mobile
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.admin-sidebar');
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
        });
    }

    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });

    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // Confirm delete actions
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(event) {
            event.preventDefault();
            const itemName = this.getAttribute('data-item-name') || 'this item';
            
            if (confirm(`Are you sure you want to delete ${itemName}? This action cannot be undone.`)) {
                window.location.href = this.href;
            }
        });
    });

    // Real-time search functionality
    const searchInput = document.getElementById('tableSearch');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const table = document.querySelector('.searchable-table');
            const rows = table.querySelectorAll('tbody tr');

            rows.forEach(function(row) {
                const text = row.textContent.toLowerCase();
                if (text.includes(filter)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }

    // Auto-refresh for real-time data
    const autoRefreshElements = document.querySelectorAll('[data-auto-refresh]');
    autoRefreshElements.forEach(function(element) {
        const interval = parseInt(element.getAttribute('data-auto-refresh')) * 1000;
        setInterval(function() {
            refreshElement(element);
        }, interval);
    });

    // Image preview functionality
    const imageInputs = document.querySelectorAll('input[type="file"][accept*="image"]');
    imageInputs.forEach(function(input) {
        input.addEventListener('change', function(event) {
            const file = event.target.files[0];
            const preview = document.getElementById(this.getAttribute('data-preview'));
            
            if (file && preview) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    });

    // Price formatting
    const priceInputs = document.querySelectorAll('.price-input');
    priceInputs.forEach(function(input) {
        input.addEventListener('blur', function() {
            const value = parseFloat(this.value);
            if (!isNaN(value)) {
                this.value = value.toFixed(2);
            }
        });
    });

    // Status update functionality
    const statusSelects = document.querySelectorAll('.status-update');
    statusSelects.forEach(function(select) {
        select.addEventListener('change', function() {
            const orderId = this.getAttribute('data-order-id');
            const newStatus = this.value;
            
            updateOrderStatus(orderId, newStatus);
        });
    });

    // Chart initialization (if Chart.js is loaded)
    if (typeof Chart !== 'undefined') {
        initializeCharts();
    }
});

/**
 * Refresh specific element content
 */
function refreshElement(element) {
    const url = element.getAttribute('data-refresh-url');
    if (!url) return;

    fetch(url)
        .then(response => response.text())
        .then(html => {
            element.innerHTML = html;
        })
        .catch(error => {
            console.error('Error refreshing element:', error);
        });
}

/**
 * Update order status via AJAX
 */
function updateOrderStatus(orderId, status) {
    const formData = new FormData();
    formData.append('order_id', orderId);
    formData.append('status', status);
    formData.append('action', 'update_status');

    fetch('ajax/update-order-status.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Order status updated successfully', 'success');
        } else {
            showNotification('Error updating order status', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error updating order status', 'error');
    });
}

/**
 * Show notification
 */
function showNotification(message, type = 'info') {
    const alertClass = type === 'error' ? 'alert-danger' : 
                      type === 'success' ? 'alert-success' : 
                      type === 'warning' ? 'alert-warning' : 'alert-info';
    
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show position-fixed" 
             style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 
                              type === 'error' ? 'exclamation-triangle' : 
                              type === 'warning' ? 'exclamation-triangle' : 'info-circle'}"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', alertHtml);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        const alert = document.querySelector('.alert:last-child');
        if (alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }
    }, 5000);
}

/**
 * Initialize dashboard charts
 */
function initializeCharts() {
    // Sales chart
    const salesChartCanvas = document.getElementById('salesChart');
    if (salesChartCanvas) {
        const salesChart = new Chart(salesChartCanvas, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Daily Sales',
                    data: [1200, 1900, 3000, 2500, 2200, 3000, 2800],
                    borderColor: '#d4af37',
                    backgroundColor: 'rgba(212, 175, 55, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Weekly Sales Overview'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value;
                            }
                        }
                    }
                }
            }
        });
    }

    // Orders chart
    const ordersChartCanvas = document.getElementById('ordersChart');
    if (ordersChartCanvas) {
        const ordersChart = new Chart(ordersChartCanvas, {
            type: 'doughnut',
            data: {
                labels: ['Dine In', 'Takeout', 'Delivery'],
                datasets: [{
                    data: [300, 150, 100],
                    backgroundColor: [
                        '#d4af37',
                        '#28a745',
                        '#17a2b8'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                    title: {
                        display: true,
                        text: 'Order Types Distribution'
                    }
                }
            }
        });
    }
}

/**
 * Export table data to CSV
 */
function exportTableToCSV(tableId, filename = 'export.csv') {
    const table = document.getElementById(tableId);
    if (!table) return;

    let csv = '';
    const rows = table.querySelectorAll('tr');

    rows.forEach(function(row) {
        const cols = row.querySelectorAll('td, th');
        const rowData = [];
        
        cols.forEach(function(col) {
            rowData.push('"' + col.textContent.replace(/"/g, '""') + '"');
        });
        
        csv += rowData.join(',') + '\n';
    });

    downloadCSV(csv, filename);
}

/**
 * Download CSV file
 */
function downloadCSV(csv, filename) {
    const csvBlob = new Blob([csv], { type: 'text/csv' });
    const csvUrl = window.URL.createObjectURL(csvBlob);
    const downloadLink = document.createElement('a');
    
    downloadLink.href = csvUrl;
    downloadLink.download = filename;
    downloadLink.click();
    
    window.URL.revokeObjectURL(csvUrl);
}

/**
 * Format currency
 */
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
}

/**
 * Validate form before submission
 */
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;

    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;

    requiredFields.forEach(function(field) {
        if (!field.value.trim()) {
            isValid = false;
            field.classList.add('is-invalid');
        } else {
            field.classList.remove('is-invalid');
        }
    });

    return isValid;
}

/**
 * Show loading spinner
 */
function showLoading(button) {
    const originalText = button.innerHTML;
    button.innerHTML = '<span class="loading-spinner"></span> Loading...';
    button.disabled = true;
    button.setAttribute('data-original-text', originalText);
}

/**
 * Hide loading spinner
 */
function hideLoading(button) {
    const originalText = button.getAttribute('data-original-text');
    if (originalText) {
        button.innerHTML = originalText;
        button.disabled = false;
        button.removeAttribute('data-original-text');
    }
}

/**
 * Auto-save form data to localStorage
 */
function autoSaveForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return;

    const formData = new FormData(form);
    const data = {};
    
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }
    
    localStorage.setItem('autosave_' + formId, JSON.stringify(data));
}

/**
 * Restore form data from localStorage
 */
function restoreForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return;

    const savedData = localStorage.getItem('autosave_' + formId);
    if (!savedData) return;

    try {
        const data = JSON.parse(savedData);
        
        for (let [key, value] of Object.entries(data)) {
            const field = form.querySelector(`[name="${key}"]`);
            if (field) {
                field.value = value;
            }
        }
    } catch (error) {
        console.error('Error restoring form data:', error);
    }
}

/**
 * Clear auto-saved form data
 */
function clearAutoSave(formId) {
    localStorage.removeItem('autosave_' + formId);
}