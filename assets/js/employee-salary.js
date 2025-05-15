/**
 * Employee Salary Page JavaScript Functions
 */

/**
 * Show allowance details modal with animation
 */
function showAllowanceDetails() {
    // Show overlay
    const overlay = document.getElementById('modal-overlay');
    overlay.style.display = 'block';
    setTimeout(() => {
        overlay.classList.add('active');
    }, 10);
    
    // Show modal with animation
    const modal = document.getElementById('allowance-details-modal');
    modal.style.display = 'block';
    setTimeout(() => {
        modal.classList.add('active');
    }, 50);
    
    // Handle close on overlay click
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) {
            hideAllowanceDetails();
        }
    });

    // Add escape key handler
    document.addEventListener('keydown', handleEscKey);
}

/**
 * Hide allowance details modal with animation
 */
function hideAllowanceDetails() {
    // Hide modal with animation
    const modal = document.getElementById('allowance-details-modal');
    modal.classList.remove('active');
    
    // Hide overlay with animation
    const overlay = document.getElementById('modal-overlay');
    overlay.classList.remove('active');
    
    // Remove elements after animation completes
    setTimeout(() => {
        modal.style.display = 'none';
        overlay.style.display = 'none';
    }, 300);
    
    // Remove escape key handler
    document.removeEventListener('keydown', handleEscKey);
}

/**
 * Handle escape key press
 * 
 * @param {KeyboardEvent} e - The keyboard event
 */
function handleEscKey(e) {
    if (e.key === 'Escape') {
        hideAllowanceDetails();
    }
}

/**
 * Group allowances by category
 * 
 * @param {Array} allowances - Array of allowance objects
 * @returns {Object} - Grouped allowances by category
 */
function groupAllowancesByCategory(allowances) {
    const categories = {
        "Dasar": ["transport_allowance", "meal_allowance"],
        "Kesejahteraan": ["health_allowance", "family_allowance"],
        "Profesional": ["position_allowance", "attendance_allowance", "communication_allowance", "education_allowance"]
    };
    
    const grouped = {};
    
    // Initialize categories
    Object.keys(categories).forEach(category => {
        grouped[category] = [];
    });
    
    // Group allowances
    allowances.forEach(allowance => {
        for (const [category, keys] of Object.entries(categories)) {
            if (keys.includes(allowance.key)) {
                grouped[category].push(allowance);
                break;
            }
        }
    });
    
    return grouped;
}

/**
 * Create allowance item element
 * 
 * @param {Object} allowance - Allowance object
 * @returns {HTMLElement} - Allowance item element
 */
function createAllowanceItem(allowance) {
    const item = document.createElement('div');
    item.className = 'allowance-item';
    item.innerHTML = `
        <span class="allowance-label">${allowance.label}</span>
        <span class="allowance-value">${allowance.value}</span>
    `;
    return item;
}

/**
 * Show payroll allowance details
 * 
 * @param {number} payrollId - Payroll ID
 */
function showPayrollAllowanceDetails(payrollId) {
    // Show modal and overlay
    showAllowanceDetails();
    
    // Show loading indicator
    const modalContent = document.querySelector('.modal-content .allowance-details');
    modalContent.innerHTML = `
        <div class="loading-indicator">
            <i class="fas fa-spinner"></i>
            <p>Loading allowance details...</p>
        </div>
    `;
    
    // Get data from API
    fetch('../api/get_payroll_details.php?id=' + payrollId)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                modalContent.innerHTML = `
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <p>${data.error}</p>
                    </div>
                `;
                return;
            }
            
            // Update modal title
            const modalTitle = document.querySelector('.modal-header h3');
            modalTitle.innerHTML = `<i class="fas fa-money-bill-wave"></i> Detail Tunjangan - ${data.period}`;
            
            // Prepare allowance data
            const allowanceItems = data.allowances.map(allowance => {
                return {
                    key: allowance.key,
                    label: allowance.label,
                    value: allowance.value
                };
            });
            
            // Group allowances by category
            const groupedAllowances = groupAllowancesByCategory(allowanceItems);
            
            // Generate HTML
            let html = `
                <div class="employee-info">
                    <h4>${data.employee.name}</h4>
                    <p>${data.employee.position}</p>
                    <p><em>Periode: ${data.period}</em></p>
                </div>
            `;
            
            // Add each category
            Object.entries(groupedAllowances).forEach(([category, allowances]) => {
                if (allowances.length > 0) {
                    html += `
                        <div class="allowance-category">
                            <h4 class="allowance-category-title">${category}</h4>
                            <div class="allowance-grid">
                                ${allowances.map(allowance => `
                                    <div class="allowance-item">
                                        <span class="allowance-label">${allowance.label}</span>
                                        <span class="allowance-value">${allowance.value}</span>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    `;
                }
            });
            
            // Add total
            html += `
                <div class="allowance-total">
                    <span class="total-label">Total Tunjangan</span>
                    <span class="total-value">${data.total_allowance}</span>
                </div>
            `;
            
            // Update modal content
            modalContent.innerHTML = html;
        })
        .catch(error => {
            console.error('Error loading allowance details:', error);
            modalContent.innerHTML = `
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <p>Failed to load allowance details. Please try again.</p>
                </div>
            `;
        });
}

/**
 * Print payslip
 * 
 * @param {number} payrollId - Payroll ID
 */
function printPayslip(payrollId) {
    // Show loading button state
    const button = event.target.closest('.btn-print');
    const originalContent = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
    button.disabled = true;
    
    // Load payroll data from server via AJAX
    fetch('../api/get_payslip.php?id=' + payrollId)
        .then(response => response.json())
        .then(data => {
            // Restore button state
            button.innerHTML = originalContent;
            button.disabled = false;
            
            // Populate the print template with payroll data
            document.getElementById('print-period').textContent = data.period;
            document.getElementById('print-base').textContent = data.base_salary;
            document.getElementById('print-transport').textContent = data.transport_allowance;
            document.getElementById('print-meal').textContent = data.meal_allowance;
            document.getElementById('print-health').textContent = data.health_allowance;
            document.getElementById('print-position').textContent = data.position_allowance;
            document.getElementById('print-attendance').textContent = data.attendance_allowance;
            document.getElementById('print-family').textContent = data.family_allowance;
            document.getElementById('print-communication').textContent = data.communication_allowance;
            document.getElementById('print-education').textContent = data.education_allowance;
            document.getElementById('print-allowance').textContent = data.total_allowance;
            document.getElementById('print-deductions').textContent = data.deductions;
            document.getElementById('print-net').textContent = data.net_salary;
            
            // Open print dialog
            const printContent = document.getElementById('payslip-template').innerHTML;
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Payslip - ${data.period}</title>
                    <link rel="stylesheet" href="../assets/css/payslip-print.css">
                </head>
                <body>
                    ${printContent}
                </body>
                </html>
            `);
            
            // Focus and print
            printWindow.document.close();
            printWindow.focus();
            setTimeout(() => {
                printWindow.print();
                printWindow.close();
            }, 250);
        })
        .catch(error => {
            console.error('Error loading payslip data:', error);
            
            // Restore button state
            button.innerHTML = originalContent;
            button.disabled = false;
            
            alert('Failed to load payslip data. Please try again.');
        });
}

/**
 * Initialize the page
 */
document.addEventListener('DOMContentLoaded', function() {
    // Create modal overlay if it doesn't exist
    if (!document.getElementById('modal-overlay')) {
        const overlay = document.createElement('div');
        overlay.id = 'modal-overlay';
        document.body.appendChild(overlay);
    }
}); 