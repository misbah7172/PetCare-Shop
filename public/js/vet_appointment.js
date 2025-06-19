document.getElementById('appointmentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    // Here you would typically handle the form submission
    alert('Appointment request submitted! We will contact you shortly to confirm.');
    this.reset();
});

// Set minimum date to today
const today = new Date().toISOString().split('T')[0];
document.getElementById('appointmentDate').min = today;

function handleCitySelection(selectElement) {
    // Hide all thana lists first
    document.querySelectorAll('.thana-list').forEach(list => {
        list.classList.remove('show');
    });

    // Show the selected city's thana list
    const selectedCity = selectElement.value;
    if (selectedCity) {
        const thanaList = document.querySelector(`.${selectedCity}-thanas`);
        if (thanaList) {
            thanaList.classList.add('show');
        }
    }
}

// Vet details data
const vetDetails = {
    "Dr. Rahman": {
        specialty: "General Veterinary Medicine",
        experience: "15 years",
        qualifications: "DVM, MS in Veterinary Medicine",
        languages: "Bengali, English",
        location: "Gulshan, Dhaka",
        workingHours: "Mon-Sat: 9:00 AM - 5:00 PM",
        fee: "৳1,500 - ৳2,500",
        emergency: "Available",
        specializations: [
            "General Health Check-ups",
            "Vaccination",
            "Preventive Care",
            "Basic Surgery"
        ],
        about: "Dr. Rahman is a highly experienced veterinarian specializing in general veterinary medicine. With 15 years of practice, he has treated thousands of pets and is known for his gentle approach and thorough diagnosis."
    },
    "Dr. Ahmed": {
        specialty: "Surgery Specialist",
        experience: "12 years",
        qualifications: "DVM, MS in Veterinary Surgery",
        languages: "Bengali, English, Hindi",
        location: "Banani, Dhaka",
        workingHours: "Mon-Fri: 10:00 AM - 6:00 PM",
        fee: "৳2,000 - ৳3,500",
        emergency: "Available",
        specializations: [
            "Soft Tissue Surgery",
            "Orthopedic Surgery",
            "Emergency Surgery",
            "Post-operative Care"
        ],
        about: "Dr. Ahmed is a skilled surgeon with extensive experience in complex surgical procedures. He has successfully performed numerous surgeries and is known for his precision and attention to detail."
    }
    // Add similar detailed information for other vets
};

// Function to show vet details
function showVetDetails(vetName) {
    const modal = document.getElementById('vetDetailsModal');
    const details = vetDetails[vetName];

    if (details) {
        document.getElementById('modalVetName').textContent = vetName;
        document.getElementById('modalSpecialty').textContent = details.specialty;
        document.getElementById('modalExperience').textContent = details.experience;
        document.getElementById('modalQualifications').textContent = details.qualifications;
        document.getElementById('modalLanguages').textContent = details.languages;
        document.getElementById('modalLocation').textContent = details.location;
        document.getElementById('modalWorkingHours').textContent = details.workingHours;
        document.getElementById('modalFee').textContent = details.fee;
        document.getElementById('modalEmergency').textContent = details.emergency;
        
        const specializationsList = document.getElementById('modalSpecializations');
        specializationsList.innerHTML = '';
        details.specializations.forEach(spec => {
            const li = document.createElement('li');
            li.textContent = spec;
            specializationsList.appendChild(li);
        });

        document.getElementById('modalAbout').textContent = details.about;
        modal.style.display = 'block';
    }
}

// Close modal when clicking the X
document.querySelector('.close-modal').onclick = function() {
    document.getElementById('vetDetailsModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('vetDetailsModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}

// Update all details buttons to use the new function
document.querySelectorAll('.details-btn').forEach(btn => {
    btn.onclick = function() {
        const vetName = this.closest('.vet-card').querySelector('h3').textContent;
        showVetDetails(vetName);
    }
}); 