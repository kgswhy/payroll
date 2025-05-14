document.addEventListener('DOMContentLoaded', function() {
    // Toggle sidebar on mobile
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    if(sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
    }
    
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
    
    // Salary calculator
    const baseSalaryInput = document.querySelector('input[name="base_salary"]');
    const allowanceInput = document.querySelector('input[name="allowance"]');
    const deductionsInput = document.querySelector('input[name="deductions"]');
    const netSalaryDisplay = document.getElementById('net-salary');
    
    if(baseSalaryInput && allowanceInput && deductionsInput && netSalaryDisplay) {
        const calculateNetSalary = function() {
            const baseSalary = parseFloat(baseSalaryInput.value) || 0;
            const allowance = parseFloat(allowanceInput.value) || 0;
            const deductions = parseFloat(deductionsInput.value) || 0;
            
            const netSalary = baseSalary + allowance - deductions;
            netSalaryDisplay.textContent = netSalary.toFixed(2);
        };
        
        baseSalaryInput.addEventListener('input', calculateNetSalary);
        allowanceInput.addEventListener('input', calculateNetSalary);
        deductionsInput.addEventListener('input', calculateNetSalary);
    }
}); 