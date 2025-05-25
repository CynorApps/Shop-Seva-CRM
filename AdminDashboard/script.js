const allSideMenu = document.querySelectorAll('#sidebar .side-menu.top li a');

allSideMenu.forEach(item=> {
    const li = item.parentElement;

    item.addEventListener('click', function () {
        allSideMenu.forEach(i=> {
            i.parentElement.classList.remove('active');
        })
        li.classList.add('active');
    })
});

// TOGGLE SIDEBAR
const menuBar = document.querySelector('#content nav .bx.bx-menu');
const sidebar = document.getElementById('sidebar');

menuBar.addEventListener('click', function () {
    sidebar.classList.toggle('hide');
})

const searchButton = document.querySelector('#content nav form .form-input button');
const searchButtonIcon = document.querySelector('#content nav form .form-input button .bx');
const searchForm = document.querySelector('#content nav form');

searchButton.addEventListener('click', function (e) {
    if(window.innerWidth < 576) {
        e.preventDefault();
        searchForm.classList.toggle('show');
        if(searchForm.classList.contains('show')) {
            searchButtonIcon.classList.replace('bx-search', 'bx-x');
        } else {
            searchButtonIcon.classList.replace('bx-x', 'bx-search');
        }
    }
})

if(window.innerWidth < 768) {
    sidebar.classList.add('hide');
} else if(window.innerWidth > 576) {
    searchButtonIcon.classList.replace('bx-x', 'bx-search');
    searchForm.classList.remove('show');
}

window.addEventListener('resize', function () {
    if(this.innerWidth > 576) {
        searchButtonIcon.classList.replace('bx-x', 'bx-search');
        searchForm.classList.remove('show');
    }
})

// DARK MODE TOGGLE WITH PERSISTENT STATE
// Function to apply dark mode based on state
function applyDarkMode(isDark) {
    console.log('Applying dark mode:', isDark);
    if (isDark) {
        document.body.classList.add('dark');
    } else {
        document.body.classList.remove('dark');
    }
    // Update toggle button state if it exists
    const switchMode = document.getElementById('switch-mode');
    if (switchMode) {
        switchMode.checked = isDark;
    } else {
        console.log('Switch mode element not found on this page');
    }
}

// On page load, check localStorage for dark mode preference
document.addEventListener('DOMContentLoaded', function () {
    console.log('Page loaded, checking dark mode preference...');
    const darkModePreference = localStorage.getItem('darkMode');
    console.log('Dark mode preference from localStorage:', darkModePreference);
    if (darkModePreference === 'true') {
        applyDarkMode(true);
    } else {
        applyDarkMode(false);
    }
});

// On toggle change, save the preference to localStorage
const switchMode = document.getElementById('switch-mode');
if (switchMode) {
    switchMode.addEventListener('change', function () {
        console.log('Dark mode toggle changed to:', this.checked);
        if(this.checked) {
            applyDarkMode(true);
            localStorage.setItem('darkMode', 'true');
        } else {
            applyDarkMode(false);
            localStorage.setItem('darkMode', 'false');
        }
        console.log('localStorage darkMode set to:', localStorage.getItem('darkMode'));
    });
} else {
    console.log('Switch mode element not found, cannot attach event listener');
}