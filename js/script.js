// js/script.js
// This file will hold form validation and interactivity as we build features.

console.log("Library Management System JS loaded successfully.");

// Admin sidebar mobile toggle
document.addEventListener("DOMContentLoaded", function () {
    const topbar = document.querySelector(".admin-mobile-topbar");
    const sidebar = document.querySelector(".admin-sidebar");
    if (topbar && sidebar) {
        topbar.addEventListener("click", function () {
            sidebar.classList.toggle("open");
        });
    }
});

// Profile photo preview on the registration page
document.addEventListener("DOMContentLoaded", function () {
    const photoInput = document.getElementById("profile_photo");
    if (!photoInput) return; // not on the registration page

    const preview = document.getElementById("photo-preview");
    const placeholder = document.getElementById("photo-placeholder");

    photoInput.addEventListener("change", function () {
        const file = this.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function (e) {
            preview.src = e.target.result;
            preview.style.display = "block";
            placeholder.style.display = "none";
        };
        reader.readAsDataURL(file);
    });
});
