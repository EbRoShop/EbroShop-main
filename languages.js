// Dictionary for the About.html content
const ebroTranslations = {
    "en": {
        "about": "by Ebro Gurage Tone",
        "true":"Select Language",
        "mission_h": "Our Mission",
        "story_h": "Our Story",
        "footer": "© 2025 EbRo-Shop. All rights reserved."
    },
    "am": {
        "about": "በኢብሮ ጉራጌ ቶን",
         "true":"ቋንቋ ይምረጡ",
        "mission_h": "ተልዕኳችን",
        "story_h": "ታሪካችን",
        "footer": "© 2025 EbRo-Shop. መብቱ በህግ የተጠበቀ ነው።"
    }
};

// Toggle the menu visibility
function toggleLangMenu() {
    document.getElementById('langOptions').classList.toggle('show');
}

// Close menu if user clicks outside
window.onclick = function(event) {
    if (!event.target.closest('.custom-lang-dropdown')) {
        document.getElementById('langOptions').classList.remove('show');
    }
}

function changeLanguage(lang) {
    localStorage.setItem("userLanguage", lang);
    document.documentElement.lang = lang;
    
    // Update the trigger label
    document.getElementById('current-lang-label').innerText = (lang === 'am') ? 'አማርኛ' : 'English';

    // Update all elements with data-key
    const elements = document.querySelectorAll("[data-key]");
    elements.forEach(el => {
        const key = el.getAttribute("data-key");
        if (ebroTranslations[lang] && ebroTranslations[lang][key]) {
            el.innerText = ebroTranslations[lang][key];
        }
    });
    
    // Hide menu after selection
    document.getElementById('langOptions').classList.remove('show');
}

// Initialize on page load
window.addEventListener('DOMContentLoaded', () => {
    const savedLang = localStorage.getItem("userLanguage");
    if (savedLang) {
        changeLanguage(savedLang);
    }
});