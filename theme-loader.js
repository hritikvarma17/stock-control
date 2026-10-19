// (function() {
//     const savedTheme = localStorage.getItem('app-theme') || 'theme-dark';
//     document.documentElement.classList.add(savedTheme); 
    
//     document.addEventListener('DOMContentLoaded', () => {
//         document.body.classList.add(savedTheme);
//     });
// })();


(function() {
    const savedTheme = localStorage.getItem('app-theme') || 'theme-dark';
    
    // Applying to html tag ensures the CSS 'html.theme-name' selectors work
    document.documentElement.classList.add(savedTheme);
    
    // Redundant but safe check for body
    const applyToBody = () => {
        if (document.body) {
            document.body.classList.add(savedTheme);
        } else {
            setTimeout(applyToBody, 5);
        }
    };
    applyToBody();
})();