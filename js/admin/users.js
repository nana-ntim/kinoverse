// Search functionality
const userSearch = document.getElementById('userSearch');
if (userSearch) {
    userSearch.addEventListener('input', debounce(function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const tableRows = document.querySelectorAll('.users-table tbody tr');

        tableRows.forEach(row => {
            const username = row.querySelector('.username').textContent.toLowerCase();
            const email = row.querySelector('.user-email').textContent.toLowerCase();
            
            if (username.includes(searchTerm) || email.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }, 300));
}

// Debounce helper
function debounce(func, wait) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

// Modal functionality
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Close modal when clicking outside
document.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal')) {
        closeModal(e.target.id);
    }
});

// View user details
function viewUser(userId) {
    window.location.href = `view_user.php?id=${userId}`;
}

// Ban user
let currentUserId = null;

function banUser(userId) {
    currentUserId = userId;
    showModal('banModal');

    const banForm = document.getElementById('banForm');
    if (banForm) {
        banForm.onsubmit = async (e) => {
            e.preventDefault();
            
            const reason = document.getElementById('banReason').value;
            const duration = document.getElementById('banDuration').value;

            try {
                const response = await fetch('../../includes/actions/admin_user_action.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=ban&user_id=${userId}&reason=${encodeURIComponent(reason)}&duration=${duration}`
                });

                const data = await response.json();
                
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Failed to ban user');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to ban user');
            }

            closeModal('banModal');
        };
    }
}

// Unban user
async function unbanUser(userId) {
    if (!confirm('Are you sure you want to unban this user?')) return;

    try {
        const response = await fetch('../../includes/actions/admin_user_action.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=unban&user_id=${userId}`
        });

        const data = await response.json();
        
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Failed to unban user');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to unban user');
    }
}

// Promote user to admin
async function promoteUser(userId) {
    if (!confirm('Are you sure you want to make this user an admin?')) return;

    try {
        const response = await fetch('../../includes/actions/admin_user_action.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=promote&user_id=${userId}`
        });

        const data = await response.json();
        
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Failed to promote user');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to promote user');
    }
}

// Delete user
async function deleteUser(userId) {
    if (!confirm('Are you sure you want to delete this user? This action cannot be undone.')) return;

    try {
        const response = await fetch('../../includes/actions/admin_user_action.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete&user_id=${userId}`
        });

        const data = await response.json();
        
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Failed to delete user');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to delete user');
    }
}

// Export users data
function exportUsers() {
    const table = document.querySelector('.users-table');
    if (!table) return;

    const rows = Array.from(table.querySelectorAll('tbody tr'));
    const csvContent = rows.map(row => {
        const username = row.querySelector('.username').textContent.trim();
        const email = row.querySelector('.user-email').textContent.trim();
        const status = row.querySelector('.badge').textContent.trim();
        return [username, email, status].join(',');
    }).join('\n');

    const headers = 'Username,Email,Status\n';
    const blob = new Blob([headers + csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'users_export.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}

// Handle escape key to close modals
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        const activeModal = document.querySelector('.modal.active');
        if (activeModal) {
            closeModal(activeModal.id);
        }
    }
});