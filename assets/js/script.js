document.addEventListener('DOMContentLoaded', function() {
    // Sidebar Toggle
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            if (mainContent) {
                mainContent.classList.toggle('sidebar-hidden');
            }
        });
    }

    // Ripple effect for buttons
    const buttons = document.querySelectorAll('button, .btn');
    buttons.forEach(button => {
        button.addEventListener('click', function(e) {
            const rect = button.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;

            const ripple = document.createElement('span');
            ripple.classList.add('ripple');
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';

            button.appendChild(ripple);

            setTimeout(() => {
                ripple.remove();
            }, 1000);
        });
    });

    // Form input animation
    const formInputs = document.querySelectorAll('.form-group input, .form-group textarea');
    formInputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });

        input.addEventListener('blur', function() {
            if (!this.value) {
                this.parentElement.classList.remove('focused');
            }
        });

        // Check on load if input has value
        if (input.value) {
            input.parentElement.classList.add('focused');
        }
    });

    // Table row hover effect
    const tableRows = document.querySelectorAll('.data-table tr');
    tableRows.forEach(row => {
        row.addEventListener('mouseover', function() {
            this.style.transition = 'background-color 0.2s ease';
        });
    });

    // Alert auto-dismiss
    const alerts = document.querySelectorAll('.alert-success, .alert-danger, .alert-warning');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    });

    // Form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let valid = true;
            
            requiredFields.forEach(field => {
                if(!field.value.trim()) {
                    valid = false;
                    // Add error class
                    field.classList.add('error');
                    // Create error message if it doesn't exist
                    if(!field.nextElementSibling || !field.nextElementSibling.classList.contains('error-message')) {
                        const errorMsg = document.createElement('span');
                        errorMsg.classList.add('error-message');
                        errorMsg.textContent = 'This field is required';
                        field.parentNode.insertBefore(errorMsg, field.nextSibling);
                    }
                } else {
                    // Remove error class and message
                    field.classList.remove('error');
                    if(field.nextElementSibling && field.nextElementSibling.classList.contains('error-message')) {
                        field.nextElementSibling.remove();
                    }
                }
            });
            
            if(!valid) {
                e.preventDefault();
            }
        });
    });
    
    // Time difference calculator for work hours
    const timeInInput = document.querySelector('input[name="time_in"]');
    const timeOutInput = document.querySelector('input[name="time_out"]');
    const totalHoursDisplay = document.getElementById('total-hours');
    
    if(timeInInput && timeOutInput && totalHoursDisplay) {
        const calculateHours = function() {
            const timeIn = timeInInput.value;
            const timeOut = timeOutInput.value;
            
            if(timeIn && timeOut) {
                const [inHours, inMinutes] = timeIn.split(':').map(Number);
                const [outHours, outMinutes] = timeOut.split(':').map(Number);
                
                let totalMinutes = (outHours * 60 + outMinutes) - (inHours * 60 + inMinutes);
                
                // Handle overnight shifts
                if(totalMinutes < 0) {
                    totalMinutes += 24 * 60;
                }
                
                const hours = Math.floor(totalMinutes / 60);
                const minutes = totalMinutes % 60;
                
                totalHoursDisplay.textContent = `${hours} hours and ${minutes} minutes`;
            }
        };
        
        timeInInput.addEventListener('change', calculateHours);
        timeOutInput.addEventListener('change', calculateHours);
    }
    
    // Confirmation for delete actions
    const deleteButtons = document.querySelectorAll('.delete-btn');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if(!confirm('Are you sure you want to delete this item?')) {
                e.preventDefault();
            }
        });
    });
    
    // Salary calculator with Rupiah format
    const formatRupiah = (number) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(number);
    };

    // Update existing salary calculator
    const baseSalaryInput = document.querySelector('input[name="base_salary"]');
    const allowanceInput = document.querySelector('input[name="allowance"]');
    const deductionsInput = document.querySelector('input[name="deductions"]');
    const netSalaryDisplay = document.getElementById('net-salary');
    
    if(baseSalaryInput && allowanceInput && deductionsInput && netSalaryDisplay) {
        const calculateNetSalary = function() {
            const baseSalary = parseFloat(baseSalaryInput.value.replace(/[^\d]/g, '')) || 0;
            const allowance = parseFloat(allowanceInput.value.replace(/[^\d]/g, '')) || 0;
            const deductions = parseFloat(deductionsInput.value.replace(/[^\d]/g, '')) || 0;
            
            const netSalary = baseSalary + allowance - deductions;
            netSalaryDisplay.textContent = formatRupiah(netSalary);
        };
        
        // Format input values on blur
        const formatInputOnBlur = (input) => {
            input.addEventListener('blur', function() {
                const value = parseFloat(this.value.replace(/[^\d]/g, '')) || 0;
                this.value = formatRupiah(value);
            });
            
            input.addEventListener('focus', function() {
                this.value = this.value.replace(/[^\d]/g, '');
            });
        };
        
        formatInputOnBlur(baseSalaryInput);
        formatInputOnBlur(allowanceInput);
        formatInputOnBlur(deductionsInput);
        
        baseSalaryInput.addEventListener('input', calculateNetSalary);
        allowanceInput.addEventListener('input', calculateNetSalary);
        deductionsInput.addEventListener('input', calculateNetSalary);
    }
}); 