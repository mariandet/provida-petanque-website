// ==========================================
// PROVIDA PÉTANQUE CLUB - COMPLETE JAVASCRIPT
// Bilingual, Admin Panel, Forms, Interactions
// ==========================================

// LANGUAGE DATA
const translations = {
    en: {
        // Common
        "home": "Home",
        "about": "About",
        "competitions": "Competitions",
        "gallery": "Gallery",
        "news": "News",
        "contact": "Contact",
        // Messages
        "registrationSubmitted": "Thank you for registering! We'll be in touch soon.",
        "contactSubmitted": "Thank you for contacting us! We'll respond shortly.",
        "registrationOpen": "Registration Open",
        "registrationClosed": "Registration Closed"
    },
    kh: {
        // Common
        "home": "ទំព័រដើម",
        "about": "អំពី",
        "competitions": "ការប្រកួតប្រជែង",
        "gallery": "រូបភាព",
        "news": "ព័ត៌មាន",
        "contact": "ទាក់ទង",
        // Messages
        "registrationSubmitted": "សូមស្វាគមន៍ក្នុងការចុះឈ្មោះ!",
        "contactSubmitted": "សូមស្វាគមន៍ក្នុងការទាក់ទង!",
        "registrationOpen": "បើកចំហរសម្រាប់ការចុះឈ្មោះ",
        "registrationClosed": "បិទសម្រាប់ការចុះឈ្មោះ"
    }
};

let currentLanguage = 'en';

// ==========================================
// INITIALIZATION
// ==========================================
document.addEventListener('DOMContentLoaded', function() {
    initializeLanguage();
    initializeNavigation();
    initializeModals();
    initializeForms();
    initializeAdmin();
    initializeGallery();
    initializeLightbox();
    initializeScrollAnimations();
});

// ==========================================
// LANGUAGE SYSTEM
// ==========================================
function initializeLanguage() {
    const langToggle = document.getElementById('langToggle');

    // Load saved language from localStorage
    const savedLang = localStorage.getItem('language') || 'en';
    currentLanguage = savedLang;

    // Update html lang attribute for Khmer font
    document.documentElement.lang = currentLanguage === 'kh' ? 'km' : 'en';
    
    // Update all text on page
    updateAllText();
    updateLanguageToggleUI();

    // Toggle button event
    if (langToggle) {
        langToggle.addEventListener('click', toggleLanguage);
    }
}

function toggleLanguage() {
    currentLanguage = currentLanguage === 'en' ? 'kh' : 'en';

    localStorage.setItem('language', currentLanguage);
    document.documentElement.lang = currentLanguage === 'kh' ? 'km' : 'en';

    updateAllText();
    updateLanguageToggleUI();
}

function updateLanguageToggleUI() {
    const enSpan = document.querySelector('.lang-en');
    const khSpan = document.querySelector('.lang-kh');

    if (!enSpan || !khSpan) return;

    if (currentLanguage === 'en') {
        enSpan.style.opacity = '1';
        khSpan.style.opacity = '0.5';
    } else {
        enSpan.style.opacity = '0.5';
        khSpan.style.opacity = '1';
    }
}

function updateAllText() {
    // Update elements with data-en and data-kh
    document.querySelectorAll('[data-en]').forEach(el => {
        const text = currentLanguage === 'en'
            ? el.getAttribute('data-en')
            : el.getAttribute('data-kh');

        if (!text) return;

        if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') {
            el.setAttribute('placeholder', text);
        } else {
            el.textContent = text;
        }
    });

    // Update placeholder-specific elements
    document.querySelectorAll('[data-en-placeholder]').forEach(el => {
        const placeholder = currentLanguage === 'en'
            ? el.getAttribute('data-en-placeholder')
            : el.getAttribute('data-kh-placeholder');

        if (placeholder) {
            el.setAttribute('placeholder', placeholder);
        }
    });
}

function updateAllText() {
    // Update data-en and data-kh elements
    document.querySelectorAll('[data-en]').forEach(el => {
        const text = currentLanguage === 'en' ? el.getAttribute('data-en') : el.getAttribute('data-kh');
        if (text) {
            if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA' || el.tagName === 'SELECT') {
                el.setAttribute('placeholder', text);
            } else if (el.dataset.label) {
                el.setAttribute('value', text);
            } else {
                el.textContent = text;
            }
        }
    });

    // Update placeholders
    document.querySelectorAll('[data-en-placeholder]').forEach(el => {
        const placeholder = currentLanguage === 'en' 
            ? el.getAttribute('data-en-placeholder') 
            : el.getAttribute('data-kh-placeholder');
        if (placeholder) {
            el.setAttribute('placeholder', placeholder);
        }
    });

    // Update button text
    document.querySelectorAll('[data-en]').forEach(el => {
        if (el.tagName === 'BUTTON' || el.tagName === 'A') {
            const text = currentLanguage === 'en' ? el.getAttribute('data-en') : el.getAttribute('data-kh');
            if (text && !el.querySelector('span')) {
                el.textContent = text;
            }
        }
    });
}

// ==========================================
// NAVIGATION
// ==========================================
function initializeNavigation() {
    const hamburger = document.getElementById('hamburger');
    const navMenu = document.getElementById('navMenu');

    if (hamburger && navMenu) {
        hamburger.addEventListener('click', () => {
            navMenu.classList.toggle('active');
            hamburger.classList.toggle('active');
        });

        // Close menu on link click
        navMenu.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                navMenu.classList.remove('active');
                hamburger.classList.remove('active');
            });
        });
    }

    // Update active nav link
    updateActiveNav();
}

function updateActiveNav() {
    const currentPage = window.location.pathname.split('/').pop() || 'index.php';
    document.querySelectorAll('.navbar__link').forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href') === currentPage) {
            link.classList.add('active');
        }
    });
}

// ==========================================
// MODALS
// ==========================================
function initializeModals() {
    const registerBtn = document.getElementById('registerMainBtn');
    const registerModal = document.getElementById('registerModal');
    const modalClose = document.querySelector('.modal__close');
    const competitionBtns = document.querySelectorAll('.event-register-btn');

    if (registerBtn && registerModal) {
        registerBtn.addEventListener('click', () => openModal(registerModal));
    }

    if (modalClose) {
        modalClose.addEventListener('click', () => closeModal(registerModal));
    }

    if (registerModal) {
        registerModal.addEventListener('click', (e) => {
            if (e.target === registerModal) closeModal(registerModal);
        });
    }

    competitionBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            if (registerModal) openModal(registerModal);
        });
    });
}

function openModal(modal) {
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modal) {
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = 'auto';
    }
}

// ==========================================
// FORMS
// ==========================================
function initializeForms() {
    const registerForm = document.getElementById('registerForm');
    const competitionForm = document.getElementById('competitionForm');
    const contactForm = document.getElementById('contactForm');
    const blogForm = document.getElementById('blogForm');

    if (registerForm) {
        registerForm.addEventListener('submit', handleRegisterSubmit);
    }
    if (competitionForm) {
        competitionForm.addEventListener('submit', handleCompetitionSubmit);
    }
    if (contactForm) {
        contactForm.addEventListener('submit', handleContactSubmit);
    }
    if (blogForm) {
        blogForm.addEventListener('submit', handleBlogSubmit);
    }
}

function handleRegisterSubmit(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = {
        name: formData.get('name'),
        email: formData.get('email'),
        phone: formData.get('phone'),
        team: formData.get('team'),
        experience: formData.get('experience'),
        date: new Date().toLocaleString()
    };

    // Save to localStorage (simulating backend)
    let registrations = JSON.parse(localStorage.getItem('registrations')) || [];
    registrations.push(data);
    localStorage.setItem('registrations', JSON.stringify(registrations));

    alert(translations[currentLanguage].registrationSubmitted);
    e.target.reset();
    const modal = document.getElementById('registerModal');
    if (modal) closeModal(modal);
}

function handleCompetitionSubmit(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = {
        name: formData.get('name'),
        email: formData.get('email'),
        phone: formData.get('phone'),
        team: formData.get('team'),
        experience: formData.get('experience'),
        players: formData.get('players'),
        event: formData.get('event'),
        date: new Date().toLocaleString()
    };

    let registrations = JSON.parse(localStorage.getItem('registrations')) || [];
    registrations.push(data);
    localStorage.setItem('registrations', JSON.stringify(registrations));

    alert(translations[currentLanguage].registrationSubmitted);
    e.target.reset();
}

function handleContactSubmit(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = {
        message: formData.get(0),
        date: new Date().toLocaleString()
    };

    let messages = JSON.parse(localStorage.getItem('contact_messages')) || [];
    messages.push(data);
    localStorage.setItem('contact_messages', JSON.stringify(messages));

    alert(translations[currentLanguage].contactSubmitted);
    e.target.reset();
}

function handleBlogSubmit(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = {
        title: formData.get('title'),
        content: formData.get('content'),
        author: formData.get('author'),
        publishDate: formData.get('publishDate'),
        id: Date.now()
    };

    let posts = JSON.parse(localStorage.getItem('blog_posts')) || [];
    posts.push(data);
    localStorage.setItem('blog_posts', JSON.stringify(posts));

    alert('Blog post published!');
    e.target.reset();
    loadBlogPosts();
}

// ==========================================
// ADMIN PANEL
// ==========================================
function initializeAdmin() {
    const adminLogin = document.getElementById('adminLogin');
    const adminDashboard = document.getElementById('adminDashboard');
    const loginForm = document.getElementById('loginForm');
    const logoutBtn = document.getElementById('logoutBtn');

    // Check if user is on admin page
    if (adminLogin && adminDashboard) {
        const isLoggedIn = sessionStorage.getItem('adminLoggedIn');
        if (isLoggedIn) {
            adminLogin.style.display = 'none';
            adminDashboard.style.display = 'block';
            loadAdminData();
        } else {
            adminLogin.style.display = 'flex';
            adminDashboard.style.display = 'none';
        }

        if (loginForm) {
            loginForm.addEventListener('submit', handleAdminLogin);
        }

        if (logoutBtn) {
            logoutBtn.addEventListener('click', () => {
                sessionStorage.removeItem('adminLoggedIn');
                location.reload();
            });
        }

        // Tab switching
        document.querySelectorAll('.admin-tab').forEach(tab => {
            tab.addEventListener('click', () => switchAdminTab(tab));
        });
    }
}

function handleAdminLogin(e) {
    e.preventDefault();
    const password = document.getElementById('adminPassword').value;
    
    if (password === 'admin123') {
        sessionStorage.setItem('adminLoggedIn', 'true');
        document.getElementById('adminLogin').style.display = 'none';
        document.getElementById('adminDashboard').style.display = 'block';
        loadAdminData();
    } else {
        alert('Incorrect password');
    }
}

function switchAdminTab(tabBtn) {
    // Remove active from all tabs
    document.querySelectorAll('.admin-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.admin-content').forEach(c => c.classList.remove('active'));

    // Add active to clicked tab
    tabBtn.classList.add('active');
    const tabName = tabBtn.getAttribute('data-tab');
    const content = document.getElementById(tabName);
    if (content) {
        content.classList.add('active');
    }
}

function loadAdminData() {
    loadRegistrationStatus();
    loadRegistrationsList();
    loadBlogPosts();
}

function loadRegistrationStatus() {
    const springReg = document.getElementById('springRegistration');
    const friendlyReg = document.getElementById('friendlyRegistration');
    const intlReg = document.getElementById('internationalRegistration');

    const status = JSON.parse(localStorage.getItem('registrationStatus')) || {
        spring: true,
        friendly: false,
        international: false
    };

    if (springReg) {
        springReg.checked = status.spring;
        springReg.addEventListener('change', () => updateRegistrationStatus('spring', springReg.checked));
    }
    if (friendlyReg) {
        friendlyReg.checked = status.friendly;
        friendlyReg.addEventListener('change', () => updateRegistrationStatus('friendly', friendlyReg.checked));
    }
    if (intlReg) {
        intlReg.checked = status.international;
        intlReg.addEventListener('change', () => updateRegistrationStatus('international', intlReg.checked));
    }

    updateStatusDisplay();
}

function updateRegistrationStatus(event, isOpen) {
    const status = JSON.parse(localStorage.getItem('registrationStatus')) || {
        spring: true,
        friendly: false,
        international: false
    };

    status[event] = isOpen;
    localStorage.setItem('registrationStatus', JSON.stringify(status));
    updateStatusDisplay();
}

function updateStatusDisplay() {
    const status = JSON.parse(localStorage.getItem('registrationStatus')) || {
        spring: true,
        friendly: false,
        international: false
    };

    const springStatus = document.getElementById('springStatus');
    const friendlyStatus = document.getElementById('friendlyStatus');
    const intlStatus = document.getElementById('internationalStatus');
    const regStatusBadge = document.getElementById('registrationStatus');

    if (springStatus) {
        springStatus.textContent = status.spring 
            ? 'Status: Open for Registration' 
            : 'Status: Closed for Registration';
    }
    if (friendlyStatus) {
        friendlyStatus.textContent = status.friendly 
            ? 'Status: Open for Registration' 
            : 'Status: Closed for Registration';
    }
    if (intlStatus) {
        intlStatus.textContent = status.international 
            ? 'Status: Open for Registration' 
            : 'Status: Closed for Registration';
    }
    if (regStatusBadge) {
        regStatusBadge.textContent = status.spring ? 'Registration Open' : 'Registration Closed';
    }
}

function loadRegistrationsList() {
    const list = document.getElementById('registrationsList');
    const registrations = JSON.parse(localStorage.getItem('registrations')) || [];

    if (list) {
        if (registrations.length === 0) {
            list.innerHTML = '<p>No registrations yet</p>';
            return;
        }

        let html = '<table><tr><th>Name</th><th>Email</th><th>Team</th><th>Experience</th><th>Date</th></tr>';
        registrations.forEach(reg => {
            html += `<tr>
                <td>${reg.name}</td>
                <td>${reg.email}</td>
                <td>${reg.team}</td>
                <td>${reg.experience}</td>
                <td>${reg.date}</td>
            </tr>`;
        });
        html += '</table>';
        list.innerHTML = html;
    }
}

function loadBlogPosts() {
    const list = document.getElementById('blogList');
    const posts = JSON.parse(localStorage.getItem('blog_posts')) || [];

    if (list) {
        let html = '<div style="display: grid; gap: 1rem;">';
        posts.forEach(post => {
            html += `<div style="background: #f5f5f5; padding: 1.5rem; border-radius: 8px; border-left: 4px solid #d4af37;">
                <h4>${post.title}</h4>
                <p><small>${post.publishDate}</small></p>
                <p>${post.content.substring(0, 100)}...</p>
                <button class="btn btn--outline" onclick="deleteBlogPost(${post.id})">Delete</button>
            </div>`;
        });
        html += '</div>';
        list.innerHTML = html;
    }
}

function deleteBlogPost(id) {
    let posts = JSON.parse(localStorage.getItem('blog_posts')) || [];
    posts = posts.filter(p => p.id !== id);
    localStorage.setItem('blog_posts', JSON.stringify(posts));
    loadBlogPosts();
}

// ==========================================
// GALLERY
// ==========================================
function initializeGallery() {
    const filterBtns = document.querySelectorAll('.filter-btn');
    const galleryItems = document.querySelectorAll('.gallery-item');

    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            // Update active button
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            // Filter items
            const filter = btn.getAttribute('data-filter');
            galleryItems.forEach(item => {
                if (filter === 'all' || item.getAttribute('data-category') === filter) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
}

// ==========================================
// LIGHTBOX
// ==========================================
function initializeLightbox() {
    const lightbox = document.getElementById('lightbox');
    if (!lightbox) return;

    const galleryItems = document.querySelectorAll('.gallery-item');
    if (!galleryItems.length) return;

    let currentIndex = 0;

    galleryItems.forEach((item, index) => {
        item.addEventListener('click', () => {
            currentIndex = index;
            openLightbox(index, galleryItems);
        });
    });

    const closeBtn = lightbox.querySelector('.lightbox-close');
    const prevBtn = lightbox.querySelector('.lightbox-prev');
    const nextBtn = lightbox.querySelector('.lightbox-next');

    if (closeBtn) closeBtn.addEventListener('click', closeLightbox);

    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            currentIndex = (currentIndex - 1 + galleryItems.length) % galleryItems.length;
            openLightbox(currentIndex, galleryItems);
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            currentIndex = (currentIndex + 1) % galleryItems.length;
            openLightbox(currentIndex, galleryItems);
        });
    }

    lightbox.addEventListener('click', (e) => {
        if (e.target === lightbox) closeLightbox();
    });

    document.addEventListener('keydown', (e) => {
        if (!lightbox.classList.contains('active')) return;

        if (e.key === 'ArrowLeft') {
            currentIndex = (currentIndex - 1 + galleryItems.length) % galleryItems.length;
            openLightbox(currentIndex, galleryItems);
        } else if (e.key === 'ArrowRight') {
            currentIndex = (currentIndex + 1) % galleryItems.length;
            openLightbox(currentIndex, galleryItems);
        } else if (e.key === 'Escape') {
            closeLightbox();
        }
    });
}

function openLightbox(index, items) {
    const lightbox = document.getElementById('lightbox');
    if (!lightbox || !items[index]) return;

    const img = items[index].querySelector('img');
    const lightboxImg = lightbox.querySelector('.lightbox-img');

    if (img && lightboxImg) {
        lightboxImg.src = img.src;
        lightboxImg.alt = img.alt || '';
        lightbox.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeLightbox() {
    const lightbox = document.getElementById('lightbox');
    if (!lightbox) return;

    lightbox.classList.remove('active');
    document.body.style.overflow = 'auto';
}

// ==========================================
// SCROLL ANIMATIONS
// ==========================================
function initializeScrollAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -100px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    document.querySelectorAll('.competition-card, .news-item, .stat-box, .event-card, .gallery-item, .video-card, .value-card, .team-card').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity 0.6s ease-out, transform 0.6s ease-out';
        observer.observe(el);
    });
}

// ==========================================
// UTILITIES
// ==========================================
// function smoothScroll(target) {
//     target.scrollIntoView({ behavior: 'smooth' });
// }

// ==========================================
// GALLERY HOVER PREVIEW
// ==========================================
// const galleryItems = document.querySelectorAll('.gallery-item');
// const hoverModal = document.getElementById('imageHoverModal');
// const hoverPreview = document.getElementById('imageHoverPreview');

// if (galleryItems.length && hoverModal && hoverPreview) {

//     galleryItems.forEach(item => {
//         const img = item.querySelector('img');
//         if (!img) return;

//         item.addEventListener('click', () => {
//             hoverPreview.src = img.src;
//             hoverPreview.alt = img.alt || '';
//             hoverModal.classList.add('active');
//         });
//     });

//     /* close when clicking modal background */
//     hoverModal.addEventListener('click', (e) => {
//         if (e.target === hoverModal) {
//             hoverModal.classList.remove('active');
//         }
//     });

// }