<script>
document.addEventListener('DOMContentLoaded', function() {
    // Close sidebar when clicking outside on mobile
    document.body.addEventListener('click', function(e) {
        if (window.innerWidth < 768) {
            const sidebar = document.getElementById('sidebarMenu');
            const toggler = document.querySelector('.navbar-toggler');
            
            if (!sidebar.contains(e.target) && !toggler.contains(e.target) && sidebar.classList.contains('show')) {
                bootstrap.Collapse.getInstance(sidebar).hide();
            }
        }
    });

    // Handle submenu active states
    const currentPath = window.location.pathname;
    const submenuLinks = document.querySelectorAll('#reportsSubmenu .nav-link');
    submenuLinks.forEach(link => {
        if (currentPath.includes(link.getAttribute('href'))) {
            link.classList.add('active');
            link.closest('.collapse').classList.add('show');
            link.closest('.nav-item').querySelector('.dropdown-toggle').classList.add('active');
        }
    });
});


document.addEventListener('DOMContentLoaded', function() {
    // Handle profile picture preview
    const profilePicInput = document.getElementById('profilePicture');
    if (profilePicInput) {
        profilePicInput.addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const profilePic = document.querySelector('.profile-picture');
                    if (profilePic) {
                        profilePic.src = e.target.result;
                    }
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    }

    // Handle profile form submission
    const profileForm = document.getElementById('profileForm');
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('update_profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Failed to update profile');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the profile');
            });
        });
    }
});
</script>
</script>