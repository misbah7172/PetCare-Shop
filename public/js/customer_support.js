// FAQ Accordion functionality
document.querySelectorAll('.faq-question').forEach(question => {
    question.addEventListener('click', () => {
        const answer = question.nextElementSibling;
        const icon = question.querySelector('i');
        // Toggle answer visibility
        answer.style.maxHeight = answer.style.maxHeight ? null : answer.scrollHeight + "px";
        // Toggle icon rotation
        icon.style.transform = icon.style.transform === 'rotate(180deg)' ? 'rotate(0deg)' : 'rotate(180deg)';
    });
});

// Form submission handling
document.getElementById('supportForm').addEventListener('submit', function(e) {
    e.preventDefault();
    alert('Thank you for your message. We will get back to you soon!');
    this.reset();
}); 